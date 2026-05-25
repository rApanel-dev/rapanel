<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\AccountLink; // Modelo para guardar los códigos
use Illuminate\Support\Str; // Para generar códigos aleatorios

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
                $user = $target;
            }
        }

        return Inertia::render('Dashboard', [
            'gameAccountsCount' => $user->gameAccounts()->count(),
            'gameAccounts' => $user->gameAccounts()
                ->select('account_id', 'userid', 'sex', 'logincount', 'lastlogin', 'last_ip', 'state', 'created_at')
                ->get(),
            'maxAccounts' => (int) config('services.ra.max_accounts', 3),
            'viewedUser'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'status' => $user->status,
            ],
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
        $newToken = 'RA-' . strtoupper(Str::random(6));
        
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
