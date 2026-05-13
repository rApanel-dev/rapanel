<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Muestra la vista principal del Panel de Control.
     */
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('Dashboard', [
            // Enviamos el conteo de cuentas
            'gameAccountsCount' => $user->gameAccounts()->count(),
            
            // Enviamos la lista de cuentas para la tabla
            'gameAccounts' => $user->gameAccounts()
                ->select('account_id', 'userid', 'sex', 'logincount', 'lastlogin', 'last_ip', 'state', 'created_at')
                ->get(),

            // Nueva variable para controlar el límite desde el .env
            // Si no existe en el .env, por defecto será 3
            'maxAccounts' => (int) env('RA_MAX_GAME_ACCOUNTS', 3),
        ]);
    }
}