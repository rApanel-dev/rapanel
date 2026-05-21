<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WhoSellController extends Controller
{
    private const PER_PAGE = 20;

    private const ELEMENTS = [
        1 => 'Water',
        2 => 'Earth',
        3 => 'Fire',
        4 => 'Wind',
    ];

    private const STARS = [
        1 => 'Very Strong',
        2 => 'Very Very Strong',
        3 => 'Very Very Very Strong',
    ];

    public function index(): Response
    {
        $search  = trim(request('search', ''));
        $sortBy  = in_array(request('sort'), ['price', 'amount', 'refine']) ? request('sort') : 'price';
        $sortDir = request('dir') === 'desc' ? 'desc' : 'asc';

        $query = DB::connection('mysql_main')
            ->table('vending_items as vi')
            ->join('vendings as v', 'v.id', '=', 'vi.vending_id')
            ->join('cart_inventory as ci', 'ci.id', '=', 'vi.cartinventory_id')
            ->join('char as c', 'c.char_id', '=', 'v.char_id')
            ->leftJoin('item_db as idb', 'idb.id', '=', 'ci.nameid')
            ->leftJoin('item_db2 as idb2', 'idb2.id', '=', 'ci.nameid')
            ->leftJoin('char as forge_char', function ($join) {
                // Solo aplica para armas forjadas (card0 = 255); el char_id del forjador
                // está codificado como card2 | (card3 << 16)
                $join->whereRaw('ci.card0 = 255')
                     ->whereRaw('forge_char.char_id = (ci.card2 | (ci.card3 << 16))');
            })
            ->select([
                'v.id as vending_id',
                'ci.nameid',
                'ci.refine',
                'ci.card0', 'ci.card1', 'ci.card2', 'ci.card3',
                'vi.amount',
                'vi.price',
                'v.title as shop_title',
                'c.name as char_name',
                'v.map',
                'v.x',
                'v.y',
                DB::raw("COALESCE(idb.name_english, idb2.name_english, CONCAT('[', ci.nameid, ']')) AS item_name"),
                DB::raw("COALESCE(idb.slots, idb2.slots, 0) AS item_slots"),
            ]);

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('idb.name_english',  'LIKE', $like)
                  ->orWhere('idb2.name_english', 'LIKE', $like)
                  ->orWhere('v.title',           'LIKE', $like)
                  ->orWhere('c.name',            'LIKE', $like)
                  ->orWhere('ci.nameid',         'LIKE', $like)
                  ->orWhere('forge_char.name',   'LIKE', $like)
                  ->orWhereRaw("ci.card0 = 255 AND CASE (ci.card1 & 0xFF)
                      WHEN 1 THEN 'Water' WHEN 2 THEN 'Earth'
                      WHEN 3 THEN 'Fire'  WHEN 4 THEN 'Wind'
                      END LIKE ?", [$like])
                  ->orWhereRaw("ci.card0 = 255 AND CASE (ci.card1 >> 8)
                      WHEN 1 THEN 'Very Strong'
                      WHEN 2 THEN 'Very Very Strong'
                      WHEN 3 THEN 'Very Very Very Strong'
                      END LIKE ?", [$like])
                  ->orWhereRaw("CAST(vi.price AS CHAR) LIKE ?", [$like])
                  ->orWhereRaw("ci.card0 != 255 AND EXISTS (
                      SELECT 1 FROM (
                          SELECT id FROM item_db  WHERE name_english LIKE ?
                          UNION
                          SELECT id FROM item_db2 WHERE name_english LIKE ?
                      ) AS _cards
                      WHERE _cards.id IN (ci.card0, ci.card1, ci.card2, ci.card3)
                  )", [$like, $like]);
            });
        }

        $query->orderBy("vi.{$sortBy}", $sortDir);

        $paginated = $query->paginate(self::PER_PAGE)->withQueryString();

        // Separate forged weapons from items with real cards
        $cardIds    = [];
        $creatorIds = [];

        foreach ($paginated->items() as $row) {
            if ((int) $row->card0 === 255) {
                // Forged weapon: creator char_id encoded across card2 (low) and card3 (high)
                $creatorId = ((int) $row->card2) | (((int) $row->card3) << 16);
                if ($creatorId > 0) {
                    $creatorIds[] = $creatorId;
                }
            } else {
                foreach (['card0', 'card1', 'card2', 'card3'] as $slot) {
                    $id = (int) $row->{$slot};
                    if ($id > 0) {
                        $cardIds[] = $id;
                    }
                }
            }
        }

        $cardNames    = $this->resolveItemNames(array_unique($cardIds));
        $creatorNames = $this->resolveCreatorNames(array_unique($creatorIds));

        $items = collect($paginated->items())->map(function ($row) use ($cardNames, $creatorNames) {
            $isForged = (int) $row->card0 === 255;

            return [
                'nameid'     => $row->nameid,
                'item_name'  => $row->item_name,
                'refine'     => (int) $row->refine,
                'forged'     => $isForged ? $this->decodeForge($row, $creatorNames) : null,
                'cards'      => $isForged ? [] : $this->resolveCards($row, $cardNames),
                'amount'     => (int) $row->amount,
                'price'      => (int) $row->price,
                'shop_title' => $row->shop_title,
                'char_name'  => $row->char_name,
                'vending_id' => (int) $row->vending_id,
                'map'        => $row->map,
                'x'          => (int) $row->x,
                'y'          => (int) $row->y,
                'slots'      => (int) $row->item_slots,
            ];
        })->values()->all();

        return Inertia::render('Info/WhoSell', [
            'items'   => $items,
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem() ?? 0,
                'to'           => $paginated->lastItem() ?? 0,
                'links'        => $paginated->linkCollection()->toArray(),
            ],
            'filters' => [
                'search' => $search,
                'sort'   => $sortBy,
                'dir'    => $sortDir,
            ],
        ]);
    }

    public function show(int $vendingId): Response|JsonResponse
    {
        $vending = DB::connection('mysql_main')
            ->table('vendings as v')
            ->join('char as c', 'c.char_id', '=', 'v.char_id')
            ->select(['v.id', 'v.title', 'v.map', 'v.x', 'v.y', 'c.name as char_name'])
            ->where('v.id', $vendingId)
            ->first();

        abort_if(! $vending, 404);

        $rows = DB::connection('mysql_main')
            ->table('vending_items as vi')
            ->join('cart_inventory as ci', 'ci.id', '=', 'vi.cartinventory_id')
            ->leftJoin('item_db as idb', 'idb.id', '=', 'ci.nameid')
            ->leftJoin('item_db2 as idb2', 'idb2.id', '=', 'ci.nameid')
            ->select([
                'ci.nameid', 'ci.refine',
                'ci.card0', 'ci.card1', 'ci.card2', 'ci.card3',
                'vi.amount', 'vi.price',
                DB::raw("COALESCE(idb.name_english, idb2.name_english, CONCAT('[', ci.nameid, ']')) AS item_name"),
                DB::raw("COALESCE(idb.slots, idb2.slots, 0) AS item_slots"),
            ])
            ->where('vi.vending_id', $vendingId)
            ->orderBy('vi.price')
            ->get();

        $cardIds = [];
        $creatorIds = [];
        foreach ($rows as $row) {
            if ((int) $row->card0 === 255) {
                $creatorId = ((int) $row->card2) | (((int) $row->card3) << 16);
                if ($creatorId > 0) $creatorIds[] = $creatorId;
            } else {
                foreach (['card0', 'card1', 'card2', 'card3'] as $slot) {
                    $id = (int) $row->{$slot};
                    if ($id > 0) $cardIds[] = $id;
                }
            }
        }

        $cardNames    = $this->resolveItemNames(array_unique($cardIds));
        $creatorNames = $this->resolveCreatorNames(array_unique($creatorIds));

        $items = $rows->map(function ($row) use ($cardNames, $creatorNames) {
            $isForged = (int) $row->card0 === 255;
            return [
                'nameid'    => $row->nameid,
                'item_name' => $row->item_name,
                'refine'    => (int) $row->refine,
                'slots'     => (int) $row->item_slots,
                'forged'    => $isForged ? $this->decodeForge($row, $creatorNames) : null,
                'cards'     => $isForged ? [] : $this->resolveCards($row, $cardNames),
                'amount'    => (int) $row->amount,
                'price'     => (int) $row->price,
            ];
        })->values()->all();

        $shopData = [
            'id'        => $vending->id,
            'title'     => $vending->title,
            'char_name' => $vending->char_name,
            'map'       => $vending->map,
            'x'         => (int) $vending->x,
            'y'         => (int) $vending->y,
        ];

        if (request()->wantsJson()) {
            return response()->json(['shop' => $shopData, 'items' => $items]);
        }

        return Inertia::render('Info/WhoSellShop', [
            'shop'  => $shopData,
            'items' => $items,
        ]);
    }

    private function resolveItemNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $in   = implode(',', array_map('intval', $ids));
        $rows = DB::connection('mysql_main')->select("
            SELECT n.id,
                   COALESCE(d.name_english, d2.name_english, CONCAT('[', n.id, ']')) AS name
            FROM (SELECT id FROM item_db WHERE id IN ({$in})
                  UNION
                  SELECT id FROM item_db2 WHERE id IN ({$in})) n
            LEFT JOIN item_db  d  ON d.id  = n.id
            LEFT JOIN item_db2 d2 ON d2.id = n.id
        ");

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = $row->name;
        }

        return $map;
    }

    private function resolveCards(object $row, array $cardNames): array
    {
        $cards = [];
        foreach (['card0', 'card1', 'card2', 'card3'] as $slot) {
            $id = (int) $row->{$slot};
            if ($id > 0) {
                $cards[] = [
                    'id'   => $id,
                    'name' => $cardNames[$id] ?? "[{$id}]",
                ];
            }
        }
        return $cards;
    }

    private function decodeForge(object $row, array $creatorNames): array
    {
        $card1     = (int) $row->card1;
        $elementId = $card1 & 0xFF;
        $stars     = $card1 >> 8;
        $charId    = ((int) $row->card2) | (((int) $row->card3) << 16);

        return [
            'element'      => self::ELEMENTS[$elementId] ?? null,
            'stars'        => min($stars, 3),
            'stars_label'  => self::STARS[min($stars, 3)] ?? null,
            'creator_name' => $charId > 0 ? ($creatorNames[$charId] ?? null) : null,
        ];
    }

    private function resolveCreatorNames(array $charIds): array
    {
        if (empty($charIds)) {
            return [];
        }

        $in   = implode(',', array_map('intval', $charIds));
        $rows = DB::connection('mysql_main')->select(
            "SELECT char_id, name FROM `char` WHERE char_id IN ({$in})"
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->char_id] = $row->name;
        }

        return $map;
    }
}
