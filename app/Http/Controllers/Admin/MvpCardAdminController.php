<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MvpCard;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MvpCardAdminController extends Controller
{
    public function index(): Response
    {
        $dbCards = MvpCard::orderBy('item_id')->get();
        $ids     = $dbCards->pluck('item_id')->all();
        $names   = $this->fetchNamesFromDb($ids);
        $counts  = $this->fetchCardCounts($ids);

        $cards = $dbCards->map(fn ($c) => [
            'id'            => $c->id,
            'item_id'       => $c->item_id,
            'name'          => $c->name_override ?? ($names[$c->item_id] ?? "Card #{$c->item_id}"),
            'name_override' => $c->name_override,
            'image_path'    => $c->image_path,
            'is_active'     => $c->is_active,
            'total'         => $counts[$c->item_id] ?? 0,
        ]);

        return Inertia::render('Admin/MvpCards/Index', [
            'cards' => $cards,
            'stats' => [
                'total'    => $cards->count(),
                'active'   => $cards->where('is_active', true)->count(),
                'inactive' => $cards->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id'       => 'required|integer|min:1|unique:mvp_cards,item_id',
            'image'         => 'required|file|mimes:jpg,jpeg,png,webp|max:512',
            'name_override' => 'nullable|string|max:255',
        ]);

        $itemId   = (int) $data['item_id'];
        $file     = $request->file('image');
        $filename = "{$itemId}.{$file->getClientOriginalExtension()}";

        $file->move(public_path('data/mvpcards'), $filename);

        MvpCard::create([
            'item_id'       => $itemId,
            'name_override' => $data['name_override'] ?: null,
            'image_path'    => $filename,
            'is_active'     => true,
        ]);

        Cache::forget('mvp_cards_data');

        return back()->with('success', __('Card added successfully.'));
    }

    public function destroy(MvpCard $mvpCard): RedirectResponse
    {
        $imagePath = public_path("data/mvpcards/{$mvpCard->image_path}");
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $mvpCard->delete();
        Cache::forget('mvp_cards_data');

        return back()->with('success', __('Card deleted successfully.'));
    }

    public function toggle(MvpCard $mvpCard): RedirectResponse
    {
        $mvpCard->update(['is_active' => ! $mvpCard->is_active]);
        Cache::forget('mvp_cards_data');

        return back()->with('success', __('Card updated successfully.'));
    }

    public function sync(): RedirectResponse
    {
        $dir = public_path('data/mvpcards');

        if (! is_dir($dir)) {
            return back()->with('error', __('Folder not found.'));
        }

        $existing = MvpCard::pluck('item_id')->flip();
        $imported = 0;

        // Fetch names from item_db for new IDs
        $newIds = [];
        foreach (scandir($dir) as $file) {
            $id = (int) pathinfo($file, PATHINFO_FILENAME);
            if ($id > 0 && ! $existing->has($id)) {
                $newIds[] = $id;
            }
        }

        if (empty($newIds)) {
            return back()->with('success', __('No new cards to import.'));
        }

        $names = $this->fetchNamesFromDb($newIds);

        foreach ($newIds as $id) {
            // Find actual filename with any extension
            $found = null;
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                if (file_exists("{$dir}/{$id}.{$ext}")) {
                    $found = "{$id}.{$ext}";
                    break;
                }
            }

            if (! $found) {
                continue;
            }

            MvpCard::create([
                'item_id'       => $id,
                'name_override' => $names[$id] ?? null,
                'image_path'    => $found,
                'is_active'     => true,
            ]);
            $imported++;
        }

        Cache::forget('mvp_cards_data');

        return back()->with('success', "{$imported} " . __('cards synced successfully.'));
    }

    public function holders(MvpCard $mvpCard): JsonResponse
    {
        $id  = $mvpCard->item_id;
        $sql = "
            SELECT char_name, char_id, account_id, userid, in_item, source
            FROM (
                -- Suelta en inventory
                SELECT c.name AS char_name, c.char_id, l.account_id, l.userid,
                       NULL AS in_item, 'inventory' AS source, l.master_id
                FROM inventory i
                JOIN `char` c ON c.char_id = i.char_id
                JOIN login l  ON l.account_id = c.account_id
                WHERE i.nameid = {$id}

                UNION ALL

                -- Insertada en ítem del inventory
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

                -- Suelta en cart_inventory
                SELECT c.name, c.char_id, l.account_id, l.userid,
                       NULL AS in_item, 'cart' AS source, l.master_id
                FROM cart_inventory ci
                JOIN `char` c ON c.char_id = ci.char_id
                JOIN login l  ON l.account_id = c.account_id
                WHERE ci.nameid = {$id}

                UNION ALL

                -- Insertada en ítem del carrito
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

                -- Suelta en storage
                SELECT NULL AS char_name, NULL AS char_id, l.account_id, l.userid,
                       NULL AS in_item, 'storage' AS source, l.master_id
                FROM storage s
                JOIN login l ON l.account_id = s.account_id
                WHERE s.nameid = {$id}

                UNION ALL

                -- Insertada en ítem del storage
                SELECT NULL, NULL, l.account_id, l.userid,
                       COALESCE(idb.name_english, idb2.name_english, CONCAT('Item #', s.nameid)) AS in_item,
                       'storage_equipped' AS source, l.master_id
                FROM storage s
                JOIN login l  ON l.account_id = s.account_id
                LEFT JOIN item_db  idb  ON idb.id  = s.nameid
                LEFT JOIN item_db2 idb2 ON idb2.id = s.nameid
                WHERE {$id} IN (s.card0, s.card1, s.card2, s.card3)

                UNION ALL

                -- Suelta en guild_storage
                SELECT NULL, NULL, NULL AS account_id, g.name AS userid,
                       NULL AS in_item, 'guild_storage' AS source, NULL AS master_id
                FROM guild_storage gs
                JOIN guild g ON g.guild_id = gs.guild_id
                WHERE gs.nameid = {$id}
            ) holders
            ORDER BY source, char_name
        ";

        $rows = DB::connection('mysql_main')->select($sql);

        // Fetch panel user names by master_id
        $masterIds = collect($rows)
            ->pluck('master_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $panelUsers = [];
        if (! empty($masterIds)) {
            $in = implode(',', array_map('intval', $masterIds));
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
            'master_id'      => isset($r->master_id) ? (int) $r->master_id : null,
        ], $rows);

        return response()->json(['holders' => $holders]);
    }

    private function fetchNamesFromDb(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $in   = implode(',', array_map('intval', $ids));
        $rows = DB::connection('mysql_main')->select("
            SELECT n.id,
                   COALESCE(d.name_english, d2.name_english) AS name
            FROM (SELECT id FROM item_db WHERE id IN ({$in})
                  UNION
                  SELECT id FROM item_db2 WHERE id IN ({$in})) n
            LEFT JOIN item_db  d  ON d.id  = n.id
            LEFT JOIN item_db2 d2 ON d2.id = n.id
        ");

        $map = [];
        foreach ($rows as $row) {
            if ($row->name) {
                $map[(int) $row->id] = $row->name;
            }
        }

        return $map;
    }

    private function fetchCardCounts(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $in  = implode(',', array_map('intval', $ids));
        $sql = "
            SELECT card_id, SUM(cnt) AS total
            FROM (
                SELECT nameid AS card_id, amount AS cnt FROM inventory      WHERE nameid IN ({$in})
                UNION ALL
                SELECT nameid, amount                   FROM cart_inventory WHERE nameid IN ({$in})
                UNION ALL
                SELECT nameid, amount                   FROM storage        WHERE nameid IN ({$in})
                UNION ALL
                SELECT nameid, amount                   FROM guild_storage  WHERE nameid IN ({$in})
                UNION ALL
                SELECT card0, 1 FROM inventory      WHERE card0 != 0 AND card0 IN ({$in})
                UNION ALL
                SELECT card1, 1 FROM inventory      WHERE card1 != 0 AND card1 IN ({$in})
                UNION ALL
                SELECT card2, 1 FROM inventory      WHERE card2 != 0 AND card2 IN ({$in})
                UNION ALL
                SELECT card3, 1 FROM inventory      WHERE card3 != 0 AND card3 IN ({$in})
                UNION ALL
                SELECT card0, 1 FROM cart_inventory WHERE card0 != 0 AND card0 IN ({$in})
                UNION ALL
                SELECT card1, 1 FROM cart_inventory WHERE card1 != 0 AND card1 IN ({$in})
                UNION ALL
                SELECT card2, 1 FROM cart_inventory WHERE card2 != 0 AND card2 IN ({$in})
                UNION ALL
                SELECT card3, 1 FROM cart_inventory WHERE card3 != 0 AND card3 IN ({$in})
                UNION ALL
                SELECT card0, 1 FROM storage        WHERE card0 != 0 AND card0 IN ({$in})
                UNION ALL
                SELECT card1, 1 FROM storage        WHERE card1 != 0 AND card1 IN ({$in})
                UNION ALL
                SELECT card2, 1 FROM storage        WHERE card2 != 0 AND card2 IN ({$in})
                UNION ALL
                SELECT card3, 1 FROM storage        WHERE card3 != 0 AND card3 IN ({$in})
                UNION ALL
                SELECT card0, 1 FROM guild_storage  WHERE card0 != 0 AND card0 IN ({$in})
                UNION ALL
                SELECT card1, 1 FROM guild_storage  WHERE card1 != 0 AND card1 IN ({$in})
                UNION ALL
                SELECT card2, 1 FROM guild_storage  WHERE card2 != 0 AND card2 IN ({$in})
                UNION ALL
                SELECT card3, 1 FROM guild_storage  WHERE card3 != 0 AND card3 IN ({$in})
            ) x
            GROUP BY card_id
        ";

        $rows = DB::connection('mysql_main')->select($sql);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->card_id] = (int) $row->total;
        }

        return $map;
    }
}
