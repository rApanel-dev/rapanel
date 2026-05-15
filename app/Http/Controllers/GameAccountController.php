<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\Character;
use App\Models\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class GameAccountController extends Controller
{
    public function show(int $accountId)
    {
        $gameAccount = GameAccount::where('account_id', $accountId)
            ->where('master_id', Auth::id())
            ->firstOrFail();

        $characters = Character::where('account_id', $accountId)
            ->orderBy('char_num')
            ->get([
                'char_id', 'char_num', 'name', 'class',
                'base_level', 'job_level', 'base_exp', 'job_exp', 'zeny',
                'str', 'agi', 'vit', DB::raw('`int`'), 'dex', 'luk',
                'hp', 'max_hp', 'sp', 'max_sp',
                'status_point', 'skill_point', 'online',
                'last_map', 'last_x', 'last_y',
                'hair', 'hair_color', 'clothes_color', 'body', 'sex',
            ]);

        $charIds = $characters->pluck('char_id')->toArray();
        $inventoryByChar = [];

        if (!empty($charIds)) {
            try {
                $placeholders = implode(',', array_fill(0, count($charIds), '?'));
                $items = DB::connection('mysql_main')->select(
                    "SELECT inv.char_id, inv.nameid, inv.amount, inv.equip,
                            inv.identify, inv.refine,
                            COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', inv.nameid)) AS name_english
                     FROM inventory AS inv
                     LEFT JOIN item_db  AS idb  ON idb.id  = inv.nameid
                     LEFT JOIN item_db2 AS idb2 ON idb2.id = inv.nameid
                     WHERE inv.char_id IN ({$placeholders})
                     ORDER BY IF(inv.equip > 0, 1, 0) DESC, inv.nameid ASC",
                    $charIds
                );

                foreach ($items as $item) {
                    $inventoryByChar[$item->char_id][] = $item;
                }
            } catch (\Exception) {
                // item_db puede no existir o tener estructura diferente
            }
        }

        $characters = $characters->map(function ($char) use ($inventoryByChar) {
            $char->inventory = $inventoryByChar[$char->char_id] ?? [];
            return $char;
        });

        return Inertia::render('GameAccount/Show', [
            'gameAccount' => $gameAccount,
            'characters'  => $characters,
        ]);
    }

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
        $newAccount = GameAccount::create([
            'userid' => $request->userid,
            'user_pass' => $password, 
            'sex' => $request->sex,
            'email' => $user->email,
            'master_id' => $user->id,
            'group_id' => 0,
            'state' => 0,
            'created_at' => now(),
        ]);

        // Dentro de la función store, después de crear la cuenta:
        ActionLog::create([
            'user_id'    => auth()->id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id' => (int) $newAccount->account_id,
                'username'   => $newAccount->userid
            ]
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

        // 5. Registro de Auditoría
        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_password_change_verified',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id' => (int) $account_id,
                'username'   => $gameAccount->userid,
                'method'     => 'master_password_confirmed' 
            ]
        ]);

        return back()->with('success', __('Password updated successfully.'));
    }

    /**
     * Obtiene el historial de actividad de una cuenta de juego específica.
     */
    public function logs($account_id)
    {
        // Verificamos que la cuenta pertenezca al usuario logueado
        $exists = GameAccount::where('account_id', $account_id)
                    ->where('master_id', auth()->id())
                    ->exists();

        if (!$exists) {
            return response()->json([], 403);
        }

        return ActionLog::on('mysql') 
            ->where('user_id', auth()->id())
            ->where('category', 'GAME_ACCOUNT')
            ->where(function($query) use ($account_id) {
                $query->where('metadata->account_id', (int)$account_id)
                    ->orWhere('metadata->account_id', (string)$account_id);
            })
            ->orderBy('created_at', 'desc')
            ->get(['id', 'action', 'ip_address', 'created_at']);
    }

    public function destroy(Request $request, $account_id)
    {
        // 1. Validar la contraseña maestra por seguridad
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        // 2. Buscar la cuenta asegurando que le pertenece
        $gameAccount = GameAccount::where('account_id', $account_id)
            ->where('master_id', auth()->id())
            ->first();

        if (!$gameAccount) {
            return back()->withErrors(['error' => __('Account not found or unauthorized.')]);
        }

        // 3. Proceso de Desactivación
        // Renombramos a 'del_timestamp_username' para liberar el nombre original
        $newName = 'del_' . time() . '_' . $gameAccount->userid;
        
        // Limitamos a 23 caracteres (máximo de rAthena en userid)
        $newName = substr($newName, 0, 23);

        $gameAccount->update([
            'userid'    => $newName,
            'master_id' => null, // O 0, según tu base de datos
            'state'     => 5,    // Estado de desactivación
        ]);

        // 4. Registrar en Auditoría
        ActionLog::create([
            'user_id'    => auth()->id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_deactivated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id' => (int) $account_id,
                'previous_username' => $gameAccount->getOriginal('userid'),
                'new_status' => 'Deactivated and Unlinked'
            ]
        ]);

        return back()->with('success', __('Account de-linked successfully. It will no longer appear in your dashboard.'));
    }
}
