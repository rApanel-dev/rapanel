<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobDb;
use App\Models\MvpCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MvpCardAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $mobDbEmpty  = !MobDb::exists();
        $classFilter = $request->query('class', 'mvp');

        $counts = [
            'mvp'    => MobDb::where('is_mvp', true)->count(),
            'boss'   => MobDb::where('is_mvp', false)->where('class', 'Boss')->count(),
            'normal' => MobDb::where('is_mvp', false)->where('class', '!=', 'Boss')->count(),
            'active' => MvpCard::where('is_active', true)->count(),
        ];

        if ($mobDbEmpty) {
            return Inertia::render('Admin/MvpCards/Index', [
                'rows'        => [],
                'counts'      => $counts,
                'classFilter' => $classFilter,
                'mobDbEmpty'  => true,
            ]);
        }

        $mobs = MobDb::when($classFilter === 'mvp',    fn($q) => $q->where('is_mvp', true))
                     ->when($classFilter === 'boss',   fn($q) => $q->where('is_mvp', false)->where('class', 'Boss'))
                     ->when($classFilter === 'normal', fn($q) => $q->where('is_mvp', false)->where('class', '!=', 'Boss'))
                     ->orderBy('name')
                     ->get(['id', 'name', 'is_mvp', 'class', 'drops']);

        // Recopilar todos los aegis names de los drops
        $allDropAegis = [];
        foreach ($mobs as $mob) {
            foreach (($mob->drops ?? []) as $drop) {
                if (!empty($drop['item'])) $allDropAegis[] = $drop['item'];
            }
        }

        // Buscar cartas en ra_item_db
        $cardMap = DB::table('item_db')
            ->whereIn('aegis_name', array_unique($allDropAegis))
            ->where('type', 'Card')
            ->get(['item_id', 'aegis_name', 'name', 'display_name'])
            ->keyBy('aegis_name');

        // Cruzar mobs con sus cartas
        $rows = [];
        foreach ($mobs as $mob) {
            foreach (($mob->drops ?? []) as $drop) {
                $aegis = $drop['item'] ?? '';
                if ($aegis && isset($cardMap[$aegis])) {
                    $card   = $cardMap[$aegis];
                    $itemId = (int) $card->item_id;
                    $rows[] = [
                        'mob_id'       => $mob->id,
                        'mob_name'     => $mob->name,
                        'is_mvp'       => (bool) $mob->is_mvp,
                        'mob_class'    => $mob->class,
                        'card_item_id' => $itemId,
                        'card_name'    => $card->display_name ?? $card->name,
                        'image_exists' => file_exists(public_path("data/items/cards/{$itemId}.png")),
                        'is_active'    => false,
                        'mvp_card_id'  => null,
                    ];
                    break; // un mob → una carta
                }
            }
        }

        // Sobreescribir estado de visibilidad desde ra_mvp_cards
        $itemIds = array_column($rows, 'card_item_id');
        $prefs   = MvpCard::whereIn('item_id', $itemIds)->get()->keyBy('item_id');

        $rows = array_map(function (array $row) use ($prefs): array {
            $pref              = $prefs->get($row['card_item_id']);
            $row['mvp_card_id'] = $pref?->id;
            $row['is_active']  = (bool) ($pref?->is_active ?? false);
            return $row;
        }, $rows);

        return Inertia::render('Admin/MvpCards/Index', [
            'rows'        => array_values($rows),
            'counts'      => $counts,
            'classFilter' => $classFilter,
            'mobDbEmpty'  => false,
        ]);
    }

    public function toggle(int $itemId, Request $request): RedirectResponse
    {
        $mobId = (int) $request->input('mob_id', 0);

        $card = MvpCard::where('item_id', $itemId)->first();

        if ($card) {
            $card->update(['is_active' => !$card->is_active]);
        } else {
            MvpCard::create([
                'item_id'  => $itemId,
                'mob_id'   => $mobId ?: null,
                'is_active'=> true,
            ]);
        }

        Cache::forget('mvp_cards_data');

        return back()->with('success', __('Card updated successfully.'));
    }

    public function holders(int $itemId): JsonResponse
    {
        $id  = $itemId;
        $sql = "
            SELECT char_name, char_id, account_id, userid, in_item, source
            FROM (
                SELECT c.name AS char_name, c.char_id, l.account_id, l.userid,
                       NULL AS in_item, 'inventory' AS source, l.master_id
                FROM inventory i
                JOIN `char` c ON c.char_id = i.char_id
                JOIN login l  ON l.account_id = c.account_id
                WHERE i.nameid = {$id}
                UNION ALL
                SELECT c.name, c.char_id, l.account_id, l.userid,
                       COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', i.nameid)) AS in_item,
                       'inventory_equipped' AS source, l.master_id
                FROM inventory i
                JOIN `char` c ON c.char_id = i.char_id
                JOIN login l  ON l.account_id = c.account_id
                LEFT JOIN item_db  idb  ON idb.id  = i.nameid
                LEFT JOIN item_db2 idb2 ON idb2.id = i.nameid
                WHERE {$id} IN (i.card0, i.card1, i.card2, i.card3)
                UNION ALL
                SELECT c.name, c.char_id, l.account_id, l.userid,
                       NULL AS in_item, 'cart' AS source, l.master_id
                FROM cart_inventory ci
                JOIN `char` c ON c.char_id = ci.char_id
                JOIN login l  ON l.account_id = c.account_id
                WHERE ci.nameid = {$id}
                UNION ALL
                SELECT c.name, c.char_id, l.account_id, l.userid,
                       COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', ci.nameid)) AS in_item,
                       'cart_equipped' AS source, l.master_id
                FROM cart_inventory ci
                JOIN `char` c ON c.char_id = ci.char_id
                JOIN login l  ON l.account_id = c.account_id
                LEFT JOIN item_db  idb  ON idb.id  = ci.nameid
                LEFT JOIN item_db2 idb2 ON idb2.id = ci.nameid
                WHERE {$id} IN (ci.card0, ci.card1, ci.card2, ci.card3)
                UNION ALL
                SELECT NULL AS char_name, NULL AS char_id, l.account_id, l.userid,
                       NULL AS in_item, 'storage' AS source, l.master_id
                FROM storage s
                JOIN login l ON l.account_id = s.account_id
                WHERE s.nameid = {$id}
                UNION ALL
                SELECT NULL, NULL, l.account_id, l.userid,
                       COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', s.nameid)) AS in_item,
                       'storage_equipped' AS source, l.master_id
                FROM storage s
                JOIN login l  ON l.account_id = s.account_id
                LEFT JOIN item_db  idb  ON idb.id  = s.nameid
                LEFT JOIN item_db2 idb2 ON idb2.id = s.nameid
                WHERE {$id} IN (s.card0, s.card1, s.card2, s.card3)
                UNION ALL
                SELECT NULL, NULL, NULL AS account_id, g.name AS userid,
                       NULL AS in_item, 'guild_storage' AS source, NULL AS master_id
                FROM guild_storage gs
                JOIN guild g ON g.guild_id = gs.guild_id
                WHERE gs.nameid = {$id}
            ) holders
            ORDER BY source, char_name
        ";

        $rows = DB::connection('mysql_main')->select($sql);

        $masterIds  = collect($rows)->pluck('master_id')->filter()->unique()->values()->all();
        $panelUsers = [];
        if (!empty($masterIds)) {
            $in    = implode(',', array_map('intval', $masterIds));
            $users = DB::select("SELECT id, name FROM ra_users WHERE id IN ({$in})");
            foreach ($users as $u) {
                $panelUsers[(int) $u->id] = $u->name;
            }
        }

        $sourceLabels = [
            'inventory'          => 'Inventory',
            'inventory_equipped' => 'Inventory (in item)',
            'cart'               => 'Cart',
            'cart_equipped'      => 'Cart (in item)',
            'storage'            => 'Storage',
            'storage_equipped'   => 'Storage (in item)',
            'guild_storage'      => 'Guild Storage',
        ];

        $holders = array_map(fn ($r) => [
            'char_name'      => $r->char_name,
            'userid'         => $r->userid,
            'account_id'     => isset($r->account_id) ? (int) $r->account_id : null,
            'location'       => $sourceLabels[$r->source] ?? $r->source,
            'in_item'        => $r->in_item,
            'master_account' => isset($r->master_id) ? ($panelUsers[(int) $r->master_id] ?? null) : null,
        ], $rows);

        return response()->json(['holders' => $holders]);
    }
}
