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
                ->select('account_id', 'userid', 'sex', 'logincount', 'lastlogin', 'last_ip', 'state')
                ->get(),
        ]);
    }
}
