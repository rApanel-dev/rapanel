<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameAccountController extends Controller
{
    public function store(Request $request)
    {
        // Traemos las configuraciones usando el prefijo unificado
        $maxAccounts = config('services.ra.max_accounts');
        $useMd5 = config('services.ra.use_md5');

        // 1. Validar los datos del formulario
        $request->validate([
            'userid' => ['required', 'string', 'min:4', 'max:23', 'unique:mysql_main.login,userid', 'regex:/^[a-zA-Z0-9_]+$/'],
            'user_pass' => ['required', 'string', 'min:4', 'max:32'],
            'sex' => ['required', 'in:M,F'],
        ], [
            // ESTA ES LA CLAVE: Personalizamos el mensaje de error y lo pasamos por __()
            'userid.unique' => __('The userid has already been taken.'),
        ]);

        $user = Auth::user();

        // 2. Limitar el número de cuentas usando la variable del .env
        $currentAccounts = GameAccount::where('master_id', $user->id)->count();
        if ($currentAccounts >= $maxAccounts) {
            return back()->withErrors([
                'userid' => __('You have reached the maximum limit of :count game accounts.', ['count' => $maxAccounts])
            ]);
        }

        // 3. Preparar la contraseña
        $password = $request->user_pass;
        if ($useMd5) {
            $password = md5($password);
        }

        // 4. Crear la cuenta en rAthena
        GameAccount::create([
            'userid' => $request->userid,
            'user_pass' => $password, 
            'sex' => $request->sex,
            'email' => $user->email,
            'master_id' => $user->id,
            'group_id' => 0,
            'state' => 0,
        ]);

        return back()->with('success', __('Game account created successfully.'));
    }
}
