<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Models\Character;
use App\Models\ActionLog;
use App\Models\DeletedAccountLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class GameAccountController extends Controller
{
    public function show(int $accountId)
    {
        $vipEnabled        = config('services.ra.vip_enabled', false);
        $bankEnabled       = config('services.ra.bank_enabled', false);
        $cashPointsEnabled = config('services.ra.cashpoints_enabled', false);

        $selectCols = [
            'account_id', 'userid', 'sex', 'email', 'group_id', 'state',
            'logincount', 'lastlogin', 'last_ip', 'birthdate',
            'character_slots', 'expiration_time', 'created_at',
        ];
        if ($vipEnabled) {
            $selectCols[] = 'vip_time';
        }

        $gameAccount = GameAccount::where('account_id', $accountId)
            ->where('master_id', Auth::id())
            ->select($selectCols)
            ->firstOrFail();

        // --- PERSONAJES con guild, party, familia y death count ---
        $characters = DB::connection('mysql_main')->select(
            "SELECT ch.char_id, ch.char_num, ch.name, ch.class,
                    ch.base_level, ch.job_level, ch.base_exp, ch.job_exp, ch.zeny,
                    ch.str, ch.agi, ch.vit, ch.`int`, ch.dex, ch.luk,
                    ch.hp, ch.max_hp, ch.sp, ch.max_sp,
                    ch.status_point, ch.skill_point, ch.online,
                    ch.last_map, ch.last_x, ch.last_y,
                    ch.save_map, ch.save_x, ch.save_y,
                    ch.hair, ch.hair_color, ch.clothes_color, ch.body, ch.sex,
                    ch.partner_id, ch.mother, ch.father, ch.child,
                    ch.party_id, ch.guild_id,
                    g.name AS guild_name,
                    g.guild_lv AS guild_level,
                    gp.name AS guild_position,
                    IFNULL(gp.exp_mode, 0) AS guild_tax,
                    p.name AS party_name,
                    pl.name AS party_leader_name,
                    p.leader_char AS party_leader_char_id,
                    partner_c.name AS partner_name,
                    mother_c.name AS mother_name,
                    father_c.name AS father_name,
                    child_c.name AS child_name,
                    IFNULL(reg.value, 0) AS death_count
             FROM `char` AS ch
             LEFT JOIN guild_member gm ON gm.char_id = ch.char_id
             LEFT JOIN guild g ON g.guild_id = gm.guild_id
             LEFT JOIN guild_position gp ON (gm.position = gp.position AND gm.guild_id = gp.guild_id)
             LEFT JOIN party p ON p.party_id = ch.party_id
             LEFT JOIN `char` pl ON pl.char_id = p.leader_char
             LEFT JOIN `char` partner_c ON partner_c.char_id = ch.partner_id
             LEFT JOIN `char` mother_c ON mother_c.char_id = ch.mother
             LEFT JOIN `char` father_c ON father_c.char_id = ch.father
             LEFT JOIN `char` child_c ON child_c.char_id = ch.child
             LEFT JOIN char_reg_num reg ON reg.char_id = ch.char_id AND reg.`key` = 'PC_DIE_COUNTER'
             WHERE ch.account_id = ?
             ORDER BY ch.char_num",
            [$accountId]
        );

        $charIds   = array_column($characters, 'char_id');
        $partyIds  = array_values(array_unique(array_filter(array_column($characters, 'party_id'))));

        $inventoryByChar   = [];
        $cartByChar        = [];
        $friendsByChar     = [];
        $partyMembersByChar = [];
        $storageItems      = [];
        $cardNames         = [];

        if (!empty($charIds)) {
            $ph = implode(',', array_fill(0, count($charIds), '?'));

            // Inventario
            try {
                $invItems = DB::connection('mysql_main')->select(
                    "SELECT inv.char_id, inv.nameid, inv.amount, inv.equip,
                            inv.identify, inv.refine, inv.attribute,
                            inv.card0, inv.card1, inv.card2, inv.card3,
                            COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', inv.nameid)) AS name_english,
                            COALESCE(idb.slots, idb2.slots, 0) AS item_slots,
                            COALESCE(idb.type, idb2.type, 3) AS item_type
                     FROM inventory AS inv
                     LEFT JOIN item_db  AS idb  ON idb.id  = inv.nameid
                     LEFT JOIN item_db2 AS idb2 ON idb2.id = inv.nameid
                     WHERE inv.char_id IN ({$ph})
                     ORDER BY IF(inv.equip > 0, 1, 0) DESC, inv.nameid ASC",
                    $charIds
                );
                foreach ($invItems as $item) {
                    $inventoryByChar[$item->char_id][] = (array) $item;
                }
            } catch (\Exception) {}

            // Inventario del carrito
            try {
                $cartItems = DB::connection('mysql_main')->select(
                    "SELECT ci.char_id, ci.nameid, ci.amount, ci.equip,
                            ci.identify, ci.refine, ci.attribute,
                            ci.card0, ci.card1, ci.card2, ci.card3,
                            COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', ci.nameid)) AS name_english,
                            COALESCE(idb.slots, idb2.slots, 0) AS item_slots,
                            COALESCE(idb.type, idb2.type, 3) AS item_type
                     FROM cart_inventory AS ci
                     LEFT JOIN item_db  AS idb  ON idb.id  = ci.nameid
                     LEFT JOIN item_db2 AS idb2 ON idb2.id = ci.nameid
                     WHERE ci.char_id IN ({$ph})
                     ORDER BY ci.nameid ASC",
                    $charIds
                );
                foreach ($cartItems as $item) {
                    $cartByChar[$item->char_id][] = (array) $item;
                }
            } catch (\Exception) {}

            // Amigos
            try {
                $friends = DB::connection('mysql_main')->select(
                    "SELECT f.char_id AS owner_char_id,
                            fr.char_id, fr.name, fr.class, fr.base_level, fr.job_level, fr.online,
                            g.name AS guild_name
                     FROM friends f
                     JOIN `char` fr ON fr.char_id = f.friend_id
                     LEFT JOIN guild_member gm ON gm.char_id = fr.char_id
                     LEFT JOIN guild g ON g.guild_id = gm.guild_id
                     WHERE f.char_id IN ({$ph})
                     ORDER BY fr.name ASC",
                    $charIds
                );
                foreach ($friends as $f) {
                    $friendsByChar[$f->owner_char_id][] = (array) $f;
                }
            } catch (\Exception) {}

            // Miembros de party
            if (!empty($partyIds)) {
                try {
                    $php = implode(',', array_fill(0, count($partyIds), '?'));
                    $pmRows = DB::connection('mysql_main')->select(
                        "SELECT pm.char_id, pm.name, pm.class, pm.base_level, pm.job_level, pm.online, pm.party_id,
                                g.name AS guild_name
                         FROM `char` pm
                         LEFT JOIN guild_member gm ON gm.char_id = pm.char_id
                         LEFT JOIN guild g ON g.guild_id = gm.guild_id
                         WHERE pm.party_id IN ({$php}) AND pm.char_id NOT IN ({$ph})
                         ORDER BY pm.name ASC",
                        array_merge($partyIds, $charIds)
                    );
                    $pmByParty = [];
                    foreach ($pmRows as $pm) {
                        $pmByParty[$pm->party_id][] = (array) $pm;
                    }
                    foreach ($characters as $char) {
                        if ($char->party_id) {
                            $partyMembersByChar[$char->char_id] = $pmByParty[$char->party_id] ?? [];
                        }
                    }
                } catch (\Exception) {}
            }
        }

        // Bank Zeny y Cash Points desde acc_reg_num
        $bankZeny   = 0;
        $cashPoints = 0;
        if ($bankEnabled || $cashPointsEnabled) {
            $keysToFetch = [];
            if ($bankEnabled)       $keysToFetch[] = '#BANKVAULT';
            if ($cashPointsEnabled) $keysToFetch[] = '#CASHPOINTS';
            try {
                $phk    = implode(',', array_fill(0, count($keysToFetch), '?'));
                $regRows = DB::connection('mysql_main')->select(
                    "SELECT `key`, `value` FROM acc_reg_num WHERE account_id = ? AND `key` IN ({$phk})",
                    array_merge([$accountId], $keysToFetch)
                );
                foreach ($regRows as $reg) {
                    if ($reg->key === '#BANKVAULT')   $bankZeny   = (int) $reg->value;
                    if ($reg->key === '#CASHPOINTS')  $cashPoints = (int) $reg->value;
                }
            } catch (\Exception) {}
        }

        // Storage de la cuenta
        try {
            $storageRows = DB::connection('mysql_main')->select(
                "SELECT st.nameid, st.amount, st.equip, st.identify, st.refine, st.attribute,
                        st.card0, st.card1, st.card2, st.card3,
                        COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', st.nameid)) AS name_english,
                        COALESCE(idb.slots, idb2.slots, 0) AS item_slots,
                        COALESCE(idb.type, idb2.type, 3) AS item_type
                 FROM storage AS st
                 LEFT JOIN item_db  AS idb  ON idb.id  = st.nameid
                 LEFT JOIN item_db2 AS idb2 ON idb2.id = st.nameid
                 WHERE st.account_id = ?
                 ORDER BY st.nameid ASC",
                [$accountId]
            );
            $storageItems = array_map(fn($i) => (array) $i, $storageRows);
        } catch (\Exception) {}

        // Lookup de nombres de cartas (batch único)
        $allItems = array_merge(
            array_merge(...array_values($inventoryByChar) ?: [[]]),
            array_merge(...array_values($cartByChar) ?: [[]]),
            $storageItems
        );
        $cardIds = [];
        foreach ($allItems as $item) {
            foreach (['card0', 'card1', 'card2', 'card3'] as $slot) {
                $id = $item[$slot] ?? 0;
                if ($id > 0 && $id !== 254 && $id !== 255) {
                    $cardIds[] = $id;
                }
            }
        }
        $cardIds = array_values(array_unique($cardIds));

        if (!empty($cardIds)) {
            try {
                $phc = implode(',', array_fill(0, count($cardIds), '?'));
                $cRows = DB::connection('mysql_main')->select(
                    "SELECT id, name_english FROM item_db WHERE id IN ({$phc})",
                    $cardIds
                );
                foreach ($cRows as $r) {
                    $cardNames[$r->id] = $r->name_english;
                }
                $missingIds = array_values(array_diff($cardIds, array_keys($cardNames)));
                if (!empty($missingIds)) {
                    $phm = implode(',', array_fill(0, count($missingIds), '?'));
                    $cRows2 = DB::connection('mysql_main')->select(
                        "SELECT id, name_english FROM item_db2 WHERE id IN ({$phm})",
                        $missingIds
                    );
                    foreach ($cRows2 as $r) {
                        $cardNames[$r->id] = $r->name_english;
                    }
                }
            } catch (\Exception) {}
        }

        // Ensamblar personajes con sus datos
        $enrichedChars = array_map(function ($char) use ($inventoryByChar, $cartByChar, $friendsByChar, $partyMembersByChar) {
            $arr = (array) $char;
            $id  = $arr['char_id'];
            $arr['inventory']     = $inventoryByChar[$id]    ?? [];
            $arr['cart_inventory'] = $cartByChar[$id]         ?? [];
            $arr['friends']       = $friendsByChar[$id]       ?? [];
            $arr['party_members'] = $partyMembersByChar[$id]  ?? [];
            return $arr;
        }, $characters);

        return Inertia::render('GameAccount/Show', [
            'gameAccount'        => $gameAccount,
            'serverName'         => config('services.ra.server_name', 'Ragnarok Online'),
            'vipEnabled'         => $vipEnabled,
            'bankEnabled'        => $bankEnabled,
            'cashPointsEnabled'  => $cashPointsEnabled,
            'bankZeny'           => $bankZeny,
            'cashPoints'         => $cashPoints,
            'characters'         => $enrichedChars,
            'storageItems'       => $storageItems,
            'cardNames'          => $cardNames,
        ]);
    }

    public function changeGender(Request $request, int $accountId)
    {
        $request->validate(['password' => ['required', 'string']]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        $gameAccount = GameAccount::where('account_id', $accountId)
            ->where('master_id', Auth::id())
            ->firstOrFail();

        $onlineCount = DB::connection('mysql_main')
            ->table('char')
            ->where('account_id', $accountId)
            ->where('online', '>', 0)
            ->count();

        if ($onlineCount > 0) {
            return back()->withErrors(['error' => __('All characters must be offline to change gender.')]);
        }

        $newSex = $gameAccount->sex === 'M' ? 'F' : 'M';

        $gameAccount->update(['sex' => $newSex]);

        DB::connection('mysql_main')
            ->table('char')
            ->where('account_id', $accountId)
            ->update(['sex' => $newSex]);

        ActionLog::create([
            'user_id'    => Auth::id(),
            'category'   => 'GAME_ACCOUNT',
            'action'     => 'game_account_gender_changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => [
                'account_id' => $accountId,
                'username'   => $gameAccount->userid,
                'new_sex'    => $newSex,
            ],
        ]);

        return back()->with('success', __('Gender changed successfully.'));
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
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->withErrors(['password' => __('The provided password does not match our records.')]);
        }

        $gameAccount = GameAccount::where('account_id', $account_id)
            ->where('master_id', auth()->id())
            ->firstOrFail();

        $originalUserid = $gameAccount->userid;
        // 'del_' (4) + account_id (máx 10 dígitos) = máx 14 chars — seguro bajo el límite de rAthena (23)
        $newUserid = 'del_' . $gameAccount->account_id;

        // Transacción externa en rAthena: si el registro en el panel falla, el update de rAthena hace rollback
        DB::connection($gameAccount->getConnectionName())->transaction(function () use ($gameAccount, $newUserid, $originalUserid, $account_id, $request) {

            $gameAccount->update([
                'userid'    => $newUserid,
                'master_id' => null,
                'state'     => 5,
            ]);

            // Transacción interna en el panel: historial dedicado + auditoría general
            DB::connection('mysql')->transaction(function () use ($originalUserid, $account_id, $request) {

                DeletedAccountLog::create([
                    'account_id'      => (int) $account_id,
                    'original_userid' => $originalUserid,
                    'web_user_id'     => auth()->id(),
                ]);

                ActionLog::create([
                    'user_id'    => auth()->id(),
                    'category'   => 'GAME_ACCOUNT',
                    'action'     => 'game_account_deactivated',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata'   => [
                        'account_id'        => (int) $account_id,
                        'previous_username' => $originalUserid,
                        'new_status'        => 'Deactivated and Unlinked',
                    ],
                ]);
            });
        });

        return redirect()->route('dashboard')->with('success', __('Account de-linked successfully. It will no longer appear in your dashboard.'));
    }
}
