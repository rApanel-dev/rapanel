<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\AccountLink;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Muestra la vista principal del Panel de Control.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Admin viewing another user's dashboard
        if ($user->role === 'Admin' && $request->filled('as')) {
            $target = \App\Models\User::find((int) $request->as);
            if ($target) {
                ActionLog::create([
                    'user_id'    => Auth::id(),
                    'category'   => 'ADMIN',
                    'action'     => 'admin_viewed_user_dashboard',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata'   => ['viewed_user_id' => $target->id, 'viewed_user_name' => $target->name],
                ]);
                $user = $target;
            }
        }

        $gameAccounts = $user->gameAccounts()
            ->select('account_id', 'userid', 'sex', 'logincount', 'lastlogin', 'last_ip', 'state', 'created_at')
            ->get();

        $cashPoints = 0;
        if (config('services.ra.cashpoints_enabled') && $gameAccounts->isNotEmpty()) {
            $cashPoints = (int) DB::connection('mysql_main')
                ->table('acc_reg_num')
                ->whereIn('account_id', $gameAccounts->pluck('account_id'))
                ->where('key', '#CASHPOINTS')
                ->sum('value');
        }

        $donationsEnabled = config('services.donations.paypal_enabled')
            || config('services.donations.stripe_enabled')
            || config('services.donations.mp_enabled');

        return Inertia::render('Dashboard', [
            'gameAccountsCount' => $gameAccounts->count(),
            'gameAccounts' => $gameAccounts,
            'maxAccounts' => (int) config('services.ra.max_accounts', 3),
            'viewedUser'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'status' => $user->status,
            ],
            'votePoints'       => (int) $user->vote_points,
            'cashPoints'       => $cashPoints,
            'donationPoints'   => $donationsEnabled ? (int) $user->donation_points : null,
        ]);
    }

    /**
     * Genera o devuelve el Token temporal para vincular cuenta en el juego.
     */
    public function getClaimToken()
    {
        $user = Auth::user();
        $minutes = 2; // Tiempo de validez

        // 1. Buscamos si ya tiene un token que NO haya expirado
        $existingLink = AccountLink::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        // 2. Si existe, devolvemos el mismo token y el tiempo real que le queda
        if ($existingLink) {
            return response()->json([
                'token' => $existingLink->token,
                'expires_in' => now()->diffInSeconds($existingLink->expires_at),
            ]);
        }

        // 3. Si no existe o expiró, limpiamos registros viejos del usuario
        AccountLink::where('user_id', $user->id)->delete();

        // 4. Creamos el nuevo token
        $newToken = 'RA-' . strtoupper(Str::random(8));
        
        $link = AccountLink::create([
            'user_id' => $user->id,
            'token' => $newToken,
            'expires_at' => now()->addMinutes($minutes),
        ]);

        return response()->json([
            'token' => $link->token,
            'expires_in' => $minutes * 60,
        ]);
    }
}
