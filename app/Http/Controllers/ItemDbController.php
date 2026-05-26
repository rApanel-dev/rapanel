<?php

namespace App\Http\Controllers;

use App\Models\ItemDb;
use App\Models\MobDb;
use App\Services\DropRateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ItemDbController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $type   = $request->input('type', '');

        $items = ItemDb::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('display_name', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('aegis_name', 'like', "%{$search}%")
                          ->orWhere('item_id', (int) $search ?: -1);
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->select([
                'item_id', 'aegis_name', 'name', 'display_name',
                'type', 'subtype', 'slots',
                'description_html', 'properties',
            ])
            ->orderBy('item_id')
            ->paginate(40)
            ->withQueryString();

        $types = ItemDb::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        return Inertia::render('ItemDb/Index', [
            'items'   => $items,
            'types'   => $types,
            'filters' => compact('search', 'type'),
        ]);
    }

    public function show(int $itemId): \Illuminate\Http\JsonResponse
    {
        $item = ItemDb::where('item_id', $itemId)->firstOrFail();

        $db = DB::connection('mysql_main');
        $serverCount = $db->table('inventory')->where('nameid', $itemId)->sum('amount')
                     + $db->table('cart_inventory')->where('nameid', $itemId)->sum('amount')
                     + $db->table('storage')->where('nameid', $itemId)->sum('amount')
                     + $db->table('guild_storage')->where('nameid', $itemId)->sum('amount');

        return response()->json(array_merge($item->toArray(), ['server_count' => (int) $serverCount]));
    }

    public function monsters(int $itemId): JsonResponse
    {
        $item = ItemDb::where('item_id', $itemId)->firstOrFail(['item_id', 'aegis_name', 'type']);

        if (!$item->aegis_name) {
            return response()->json([]);
        }

        $aegis = $item->aegis_name;

        $mobs = MobDb::query()
            ->where(function ($q) use ($aegis) {
                $q->whereRaw("JSON_SEARCH(LOWER(drops), 'one', LOWER(?), NULL, '\$[*].item') IS NOT NULL", [$aegis])
                  ->orWhereRaw("JSON_SEARCH(LOWER(mvp_drops), 'one', LOWER(?), NULL, '\$[*].item') IS NOT NULL", [$aegis]);
            })
            ->select(['id', 'aegis_name', 'name', 'level', 'hp', 'is_mvp', 'class', 'element', 'race', 'drops', 'mvp_drops'])
            ->orderBy('level')
            ->get()
            ->map(function ($mob) use ($aegis, $item) {
                $rate      = null;
                $isMvpDrop = false;

                foreach ($mob->drops ?? [] as $d) {
                    if (strcasecmp($d['item'] ?? '', $aegis) === 0) {
                        $rate = $d['rate'];
                        break;
                    }
                }

                if ($rate === null) {
                    foreach ($mob->mvp_drops ?? [] as $d) {
                        if (strcasecmp($d['item'] ?? '', $aegis) === 0) {
                            $rate      = $d['rate'];
                            $isMvpDrop = true;
                            break;
                        }
                    }
                }

                $isMvp  = (bool) $mob->is_mvp;
                $isBoss = !$isMvp && $mob->class === 'Boss';

                return [
                    'id'            => $mob->id,
                    'name'          => $mob->name,
                    'level'         => $mob->level,
                    'hp'            => $mob->hp,
                    'is_mvp'        => $isMvp,
                    'class'         => $mob->class,
                    'element'       => $mob->element,
                    'race'          => $mob->race,
                    'rate'          => $rate,
                    'adjusted_rate' => $rate !== null
                        ? DropRateCalculator::calculate(
                            (int) $rate,
                            $item->type ?? 'Etc',
                            $isMvp,
                            $isBoss,
                            $isMvpDrop
                        )
                        : null,
                    'is_mvp_drop'   => $isMvpDrop,
                ];
            });

        return response()->json($mobs->values());
    }

    public function trade(int $itemId): JsonResponse
    {
        $rows = DB::connection('mysql_main')
            ->table('vending_items as vi')
            ->join('vendings as v', 'v.id', '=', 'vi.vending_id')
            ->join('cart_inventory as ci', 'ci.id', '=', 'vi.cartinventory_id')
            ->join('char as c', 'c.char_id', '=', 'v.char_id')
            ->where('ci.nameid', $itemId)
            ->select([
                'v.id as vending_id',
                'v.title as shop_title',
                'c.name as char_name',
                'v.map',
                'v.x',
                'v.y',
                'ci.refine',
                'vi.amount',
                'vi.price',
            ])
            ->orderBy('vi.price')
            ->limit(100)
            ->get()
            ->toArray();

        return response()->json($rows);
    }
}
