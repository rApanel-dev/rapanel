<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GameAccountAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = GameAccount::query();

        // Main search: userid, email, acc_id, master_id, master name/email (cross-DB)
        if ($request->filled('search')) {
            $term = $request->search;

            $matchingMasterIds = User::where('name', 'like', "%{$term}%")
                                     ->orWhere('email', 'like', "%{$term}%")
                                     ->pluck('id');

            $query->where(function ($q) use ($term, $matchingMasterIds) {
                $q->where('userid', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");

                if (is_numeric($term)) {
                    $q->orWhere('account_id', (int) $term)
                      ->orWhere('master_id', (int) $term);
                }

                if ($matchingMasterIds->isNotEmpty()) {
                    $q->orWhereIn('master_id', $matchingMasterIds);
                }
            });
        }

        if ($request->filled('state'))  { $query->where('state', $request->state); }
        if ($request->filled('gender')) { $query->where('sex', $request->gender); }

        // Advanced filters
        if ($request->filled('acc_id'))   { $query->where('account_id', (int) $request->acc_id); }
        if ($request->filled('group_id')) { $query->where('group_id', (int) $request->group_id); }

        if ($request->filled('last_ip')) {
            $query->where('last_ip', 'like', "%{$request->last_ip}%");
        }

        if ($request->filled('linked')) {
            $request->linked === '1'
                ? $query->whereNotNull('master_id')
                : $query->whereNull('master_id');
        }

        if ($request->filled('login_from')) {
            $query->where('lastlogin', '>=', $request->login_from . ' 00:00:00');
        }
        if ($request->filled('login_to')) {
            $query->where('lastlogin', '<=', $request->login_to . ' 23:59:59');
        }

        if ($request->filled('master_id')) {
            $query->where('master_id', (int) $request->master_id);
        }

        // Cross-DB: search master user by name or email
        if ($request->filled('master_search')) {
            $masterIds = User::where('name', 'like', "%{$request->master_search}%")
                             ->orWhere('email', 'like', "%{$request->master_search}%")
                             ->pluck('id');
            $query->whereIn('master_id', $masterIds);
        }

        $accounts = $query->orderByDesc('account_id')->paginate(25)->withQueryString();

        // Resolve master user info (cross-DB, 2 queries max)
        $masterIds   = $accounts->pluck('master_id')->filter()->unique()->values();
        $masterUsers = User::whereIn('id', $masterIds)->select('id', 'name', 'email')->get()->keyBy('id');

        $accounts->each(function ($account) use ($masterUsers) {
            $user = $masterUsers->get($account->master_id);
            $account->master_name  = $user?->name;
            $account->master_email = $user?->email;
        });

        return Inertia::render('Admin/GameAccounts/Index', [
            'accounts' => $accounts,
            'filters'  => $request->only([
                'search', 'state', 'gender',
                'acc_id', 'group_id', 'last_ip', 'linked',
                'login_from', 'login_to', 'master_id', 'master_search',
            ]),
        ]);
    }

    public function ban(Request $request, int $accountId)
    {
        $data = $request->validate([
            'type'   => 'required|in:permanent,temporary',
            'days'   => 'required_if:type,temporary|nullable|integer|min:1|max:3650',
            'reason' => 'nullable|string|max:255',
        ]);

        $account = GameAccount::where('account_id', $accountId)->firstOrFail();

        $unbanTime = 0;
        if ($data['type'] === 'temporary') {
            $unbanTime = now()->addDays((int) $data['days'])->timestamp;
        }

        $account->update(['state' => 1, 'unban_time' => $unbanTime]);

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_banned',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => array_filter([
                'account_id'   => $accountId,
                'username'     => $account->userid,
                'ban_type'     => $data['type'],
                'days'         => $data['type'] === 'temporary' ? (int) $data['days'] : null,
                'unban_time'   => $unbanTime ?: null,
                'reason'       => $data['reason'] ?? null,
                'admin_name'   => Auth::user()->name,
                'admin_override' => true,
            ]),
        ]);

        return back()->with('success', __('Account banned successfully.'));
    }

    public function unban(Request $request, int $accountId)
    {
        $account = GameAccount::where('account_id', $accountId)->firstOrFail();

        $account->update(['state' => 0, 'unban_time' => 0]);

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_unbanned',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id'     => $accountId,
                'username'       => $account->userid,
                'admin_name'     => Auth::user()->name,
                'admin_override' => true,
            ],
        ]);

        return back()->with('success', __('Account unbanned successfully.'));
    }

    public function setGroup(Request $request, int $accountId)
    {
        $data = $request->validate([
            'group_id' => 'required|integer|min:0|max:99',
        ]);

        $account = GameAccount::where('account_id', $accountId)->firstOrFail();
        $oldGroup = $account->group_id;

        $account->update(['group_id' => $data['group_id']]);

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_group_changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id'     => $accountId,
                'username'       => $account->userid,
                'from_group'     => $oldGroup,
                'to_group'       => $data['group_id'],
                'admin_name'     => Auth::user()->name,
                'admin_override' => true,
            ],
        ]);

        return back()->with('success', __('Group updated successfully.'));
    }
}
