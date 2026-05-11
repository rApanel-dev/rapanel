<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    public function changePassword(Request $request, $account_id)
    {
        // 1. Añadimos 'current_password' a la validación
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'min:4', 'max:32'],
        ], [
            'current_password.required' => __('Please enter your master account password.'),
            'password.confirmed' => __('The password confirmation does not match.'),
        ]);

        // 2. LA CLAVE: Validar que la contraseña maestra coincida
        // Comparamos lo que escribió el usuario con el hash en ra_users
        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors([
                'current_password' => __('The provided password does not match our records.')
            ]);
        }

        // 3. Verificar propiedad de la cuenta de juego (Seguridad Crítica)
        $gameAccount = GameAccount::where('account_id', $account_id)
            ->where('master_id', Auth::id())
            ->first();

        if (!$gameAccount) {
            return back()->withErrors(['error' => __('You do not have permission to manage this account.')]);
        }

        // 4. Preparar y actualizar contraseña de rAthena (MD5 o Texto Plano)
        $useMd5 = config('services.ra.use_md5');
        $newPassword = $request->password;
        
        if ($useMd5) {
            $newPassword = md5($newPassword);
        }

        $gameAccount->update([
            'user_pass' => $newPassword
        ]);

        // 5. Registro de Auditoría (Importante incluir que fue verificada)
        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'security',
            'action'     => 'game_account_password_change_verified',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id' => $account_id,
                'username'   => $gameAccount->userid,
                'method'     => 'master_password_confirmed' 
            ]
        ]);

        return back()->with('success', __('Password updated successfully.'));
    }
}
