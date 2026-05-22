<?php

namespace App\Http\Controllers;

use App\Models\MvpCard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MvpCardController extends Controller
{
    public function index(): Response
    {
        $cards = Cache::remember('mvp_cards_data', 60, function () {
            return $this->buildCardData();
        });

        return Inertia::render('Info/MvpCard', [
            'cards' => $cards,
        ]);
    }

    private function buildCardData(): array
    {
        $dbCards = MvpCard::where('is_active', true)->orderBy('item_id')->get();

        if ($dbCards->isEmpty()) {
            return [];
        }

        $cardIds = $dbCards->pluck('item_id')->all();
        $names   = $this->fetchCardNames($cardIds);
        $counts  = $this->fetchCardCounts($cardIds);

        return $dbCards->map(function (MvpCard $card) use ($names, $counts) {
            return [
                'id'    => $card->item_id,
                'name'  => $card->name_override ?? ($names[$card->item_id] ?? "Card #{$card->item_id}"),
                'total' => $counts[$card->item_id] ?? 0,
            ];
        })->toArray();
    }

    private function fetchCardNames(array $ids): array
    {
        $map = [];

        DB::table('item_db')
            ->whereIn('item_id', $ids)
            ->get(['item_id', 'display_name', 'name'])
            ->each(function ($row) use (&$map) {
                $map[(int) $row->item_id] = $row->display_name ?? $row->name;
            });

        return $map;
    }

    private function fetchCardCounts(array $ids): array
    {
        $in = implode(',', array_map('intval', $ids));

        $sql = "
            SELECT card_id, SUM(cnt) AS total
            FROM (
                SELECT nameid AS card_id, amount AS cnt FROM inventory       WHERE nameid IN ({$in})
                UNION ALL
                SELECT nameid, amount                   FROM cart_inventory  WHERE nameid IN ({$in})
                UNION ALL
                SELECT nameid, amount                   FROM storage         WHERE nameid IN ({$in})
                UNION ALL
                SELECT nameid, amount                   FROM guild_storage   WHERE nameid IN ({$in})
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
