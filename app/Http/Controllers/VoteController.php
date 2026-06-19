<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\GameAccount;
use App\Models\SiteSetting;
use App\Models\UserVote;
use App\Models\VoteSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VoteController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $sites = VoteSite::where('is_active', true)->orderBy('sort_order')->get();

        $lastVotes = $sites->isNotEmpty()
            ? UserVote::where('user_id', $user->id)
                ->whereIn('vote_site_id', $sites->pluck('id'))
                ->latest('voted_at')
                ->get()
                ->groupBy('vote_site_id')
                ->map(fn ($g) => $g->first())
            : collect();

        $userVotes = [];
        foreach ($sites as $site) {
            $last       = $lastVotes->get($site->id);
            $nextVoteAt = $last ? $last->voted_at->addHours($site->cooldown_hours) : null;
            $canVote    = ! $nextVoteAt || now()->gte($nextVoteAt);

            $userVotes[$site->id] = [
                'can_vote'     => $canVote,
                'next_vote_at' => $canVote ? null : $nextVoteAt->toISOString(),
            ];
        }

        $cashPointsEnabled = (bool) config('services.ra.cashpoints_enabled');
        $gameAccounts = $cashPointsEnabled
            ? $user->gameAccounts()->select('account_id', 'userid')->get()
            : collect();

        // Detectar qué cuentas tienen personajes online para bloquear la conversión
        $onlineAccountIds = [];
        $cashPointsByAccount = [];
        if ($gameAccounts->isNotEmpty()) {
            $accountIds = $gameAccounts->pluck('account_id')->toArray();

            $onlineAccountIds = DB::connection('mysql_main')
                ->table('char')
                ->whereIn('account_id', $accountIds)
                ->where('online', '>', 0)
                ->pluck('account_id')
                ->unique()
                ->values()
                ->toArray();

            $cpRows = DB::connection('mysql_main')
                ->table('acc_reg_num')
                ->whereIn('account_id', $accountIds)
                ->where('key', '#CASHPOINTS')
                ->get(['account_id', 'value']);

            foreach ($cpRows as $row) {
                $cashPointsByAccount[$row->account_id] = (int) $row->value;
            }
        }

        return Inertia::render('Vote/Index', [
            'sites'             => $sites->map(fn ($s) => [
                'id'              => $s->id,
                'name'            => $s->name,
                'icon_url'        => $s->icon_url,
                'vote_url'        => $s->buildVoteUrl($user->id),
                'callback_type'   => $s->callback_type,
                'points_per_vote' => $s->points_per_vote,
                'cooldown_hours'  => $s->cooldown_hours,
            ]),
            'userVotes'         => $userVotes,
            'votePoints'        => (int) $user->vote_points,
            'cashPointsEnabled' => $cashPointsEnabled,
            'gameAccounts'      => $gameAccounts->map(fn ($a) => [
                'account_id'  => $a->account_id,
                'userid'      => $a->userid,
                'cash_points' => $cashPointsByAccount[$a->account_id] ?? 0,
            ]),
            'onlineAccountIds'  => $onlineAccountIds,
            'conversionRate'    => [
                'from' => (int) SiteSetting::getValue('vote_cash_from', 10),
                'to'   => (int) SiteSetting::getValue('vote_cash_to', 100),
            ],
        ]);
    }

    // Honor system: el click registra el voto inmediatamente y abre la URL externamente
    public function click(Request $request, VoteSite $site)
    {
        if (! $site->is_active || $site->callback_type !== 'honor') {
            abort(403);
        }

        $user = Auth::user();

        DB::transaction(function () use ($user, $site, $request) {
            $last = UserVote::where('user_id', $user->id)
                ->where('vote_site_id', $site->id)
                ->latest('voted_at')
                ->lockForUpdate()
                ->first();

            if ($last && now()->lt($last->voted_at->addHours($site->cooldown_hours))) {
                return;
            }

            UserVote::create([
                'user_id'        => $user->id,
                'vote_site_id'   => $site->id,
                'voted_at'       => now(),
                'points_awarded' => $site->points_per_vote,
                'ip_address'     => $request->ip(),
            ]);

            $user->increment('vote_points', $site->points_per_vote);

            ActionLog::create([
                'user_id'    => $user->id,
                'category'   => 'VOTE',
                'action'     => 'vote_click_registered',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata'   => ['site_id' => $site->id, 'site_name' => $site->name, 'points' => $site->points_per_vote],
            ]);
        });

        return response()->json([
            'next_vote_at' => now()->addHours($site->cooldown_hours)->toISOString(),
            'points'       => $site->points_per_vote,
        ]);
    }

    // Callback: el sitio externo notifica a rapanel cuando alguien vota
    public function callback(Request $request, VoteSite $site)
    {
        if (! $site->is_active || $site->callback_type !== 'callback') {
            return response('Not found', 404);
        }

        $userId = $this->validateCallback($request, $site);

        if (! $userId) {
            Log::warning('Vote callback failed validation', [
                'site_id' => $site->id,
                'ip'      => $request->ip(),
            ]);
            return response('Invalid request', 403);
        }

        $user = \App\Models\User::find((int) $userId);
        if (! $user) {
            return response('User not found', 404);
        }

        DB::transaction(function () use ($user, $site, $request) {
            $last = UserVote::where('user_id', $user->id)
                ->where('vote_site_id', $site->id)
                ->latest('voted_at')
                ->lockForUpdate()
                ->first();

            if ($last && now()->lt($last->voted_at->addHours($site->cooldown_hours))) {
                return;
            }

            UserVote::create([
                'user_id'        => $user->id,
                'vote_site_id'   => $site->id,
                'voted_at'       => now(),
                'points_awarded' => $site->points_per_vote,
                'ip_address'     => $request->ip(),
            ]);

            $user->increment('vote_points', $site->points_per_vote);

            ActionLog::create([
                'user_id'    => $user->id,
                'category'   => 'VOTE',
                'action'     => 'vote_received',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata'   => ['site_id' => $site->id, 'site_name' => $site->name, 'points' => $site->points_per_vote],
            ]);
        });

        return response('OK', 200);
    }

    public function convert(Request $request)
    {
        $from = (int) SiteSetting::getValue('vote_cash_from', 10);
        $to   = (int) SiteSetting::getValue('vote_cash_to', 100);

        $validated = $request->validate([
            'vote_points' => ['required', 'integer', 'min:' . $from],
            'account_id'  => ['required', 'integer'],
        ]);

        $user      = Auth::user();
        $vpSpend   = (int) $validated['vote_points'];
        $accountId = (int) $validated['account_id'];

        if ($vpSpend % $from !== 0) {
            return back()->withErrors(['vote_points' => __('Vote points must be a multiple of :n.', ['n' => $from])]);
        }

        if ($user->vote_points < $vpSpend) {
            return back()->withErrors(['vote_points' => __('Insufficient vote points.')]);
        }

        $account = GameAccount::where('account_id', $accountId)
            ->where('master_id', $user->id)
            ->first();

        if (! $account) {
            return back()->withErrors(['account_id' => __('Game account not found.')]);
        }

        $hasOnlineChars = DB::connection('mysql_main')
            ->table('char')
            ->where('account_id', $account->account_id)
            ->where('online', '>', 0)
            ->exists();

        if ($hasOnlineChars) {
            return back()->withErrors(['account_id' => __('You must log out all characters before converting vote points.')]);
        }

        $cashGain = intdiv($vpSpend, $from) * $to;

        DB::transaction(function () use ($user, $account, $vpSpend, $cashGain, $request) {
            $user->decrement('vote_points', $vpSpend);

            DB::connection('mysql_main')->table('acc_reg_num')->updateOrInsert(
                ['account_id' => $account->account_id, 'key' => '#CASHPOINTS'],
                ['value' => DB::raw('value + ' . (int) $cashGain)]
            );

            ActionLog::create([
                'user_id'    => $user->id,
                'category'   => 'VOTE',
                'action'     => 'vote_points_converted',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata'   => [
                    'vote_points_spent'  => $vpSpend,
                    'cash_points_gained' => $cashGain,
                    'account_id'         => $account->account_id,
                    'userid'             => $account->userid,
                ],
            ]);
        });

        return back()->with('success', __('Converted :vp vote points into :cp cash points for :user.', [
            'vp'   => $vpSpend,
            'cp'   => $cashGain,
            'user' => $account->userid,
        ]));
    }

    // Validación genérica para cualquier sitio con callback:
    // - Si tiene callback_ip configurada: valida que la request venga de esa IP
    // - Si tiene callback_secret configurada: valida el parámetro 'secret' o 'key'
    // - El user_id se extrae de: user_id, p_resp, custom, pseudo (en ese orden)
    private function validateCallback(Request $request, VoteSite $site): ?int
    {
        if ($site->callback_ip && $request->ip() !== $site->callback_ip) {
            return null;
        }

        if ($site->callback_secret) {
            $received = $request->input('secret')
                ?? $request->input('key')
                ?? $request->input('api_token')
                ?? $request->header('Authorization');

            if ($received !== $site->callback_secret) {
                return null;
            }
        }

        $userId = $request->input('user_id')
            ?? $request->query('user_id')
            ?? $request->query('p_resp')
            ?? $request->query('custom')
            ?? $request->input('pseudo');

        return is_numeric($userId) ? (int) $userId : null;
    }
}
