<?php

namespace App\Services;

use App\Models\DropRate;
use Illuminate\Support\Facades\Cache;

class DropRateCalculator
{
    // Official rAthena defaults (rates.conf / drops.conf)
    private const DEFAULTS = [
        'item_rate_common'       => 100,
        'item_rate_common_boss'  => 100,
        'item_rate_common_mvp'   => 100,
        'item_drop_common_min'   => 1,
        'item_drop_common_max'   => 10000,
        'item_rate_heal'         => 100,
        'item_rate_heal_boss'    => 100,
        'item_rate_heal_mvp'     => 100,
        'item_drop_heal_min'     => 1,
        'item_drop_heal_max'     => 10000,
        'item_rate_use'          => 100,
        'item_rate_use_boss'     => 100,
        'item_rate_use_mvp'      => 100,
        'item_drop_use_min'      => 1,
        'item_drop_use_max'      => 10000,
        'item_rate_equip'        => 100,
        'item_rate_equip_boss'   => 100,
        'item_rate_equip_mvp'    => 100,
        'item_drop_equip_min'    => 1,
        'item_drop_equip_max'    => 10000,
        'item_rate_card'         => 100,
        'item_rate_card_boss'    => 100,
        'item_rate_card_mvp'     => 100,
        'item_drop_card_min'     => 1,
        'item_drop_card_max'     => 10000,
        'item_rate_mvp'          => 100,
        'item_drop_mvp_min'      => 1,
        'item_drop_mvp_max'      => 10000,
        'drop_rate_cap'          => 0,
        'drop_rate_cap_vip'      => 0,
    ];

    public static function rates(): array
    {
        return Cache::remember('ra_drop_rates_calc', 300, function () {
            $db = DropRate::pluck('value', 'key')->toArray();
            return array_merge(self::DEFAULTS, $db);
        });
    }

    /**
     * Returns the server-adjusted drop rate as a percentage (0–100).
     *
     * @param  int    $baseRate   Raw rate from mob DB (0–10000)
     * @param  string $itemType   Item type string (Weapon, Armor, Card, Healing, etc.)
     * @param  bool   $isMvp      Monster is a true MVP (is_mvp=1 AND class=Boss)
     * @param  bool   $isBoss     Monster is a Boss-class but not MVP
     * @param  bool   $isMvpDrop  Drop comes from the MVP drop list
     */
    public static function calculate(int $baseRate, string $itemType, bool $isMvp, bool $isBoss, bool $isMvpDrop): float
    {
        $rates = self::rates();

        if ($isMvpDrop) {
            $mult = $rates['item_rate_mvp'];
            $min  = $rates['item_drop_mvp_min'];
            $max  = $rates['item_drop_mvp_max'];
        } else {
            $cat  = self::itemCategory($itemType);
            $tier = $isMvp ? 'mvp' : ($isBoss ? 'boss' : '');

            $rateKey = $tier !== '' ? "item_rate_{$cat}_{$tier}" : "item_rate_{$cat}";
            $mult    = $rates[$rateKey]              ?? 100;
            $min     = $rates["item_drop_{$cat}_min"] ?? 1;
            $max     = $rates["item_drop_{$cat}_max"] ?? 10000;
        }

        // drop_rate_cap is NOT applied here — it only limits player-side bonuses
        // (Bubble Gum, drop cards, VIP bonuses), not server-side multipliers.
        $adjusted = $baseRate * $mult / 10000;
        $minPct   = $min / 100;
        $maxPct   = $max / 100;

        return min(max($adjusted, $minPct), $maxPct);
    }

    private static function itemCategory(string $type): string
    {
        return match ($type) {
            'Healing'                         => 'heal',
            'Usable', 'Cash', 'Delayconsume' => 'use',
            'Weapon', 'Armor', 'Petarmor'    => 'equip',
            'Card'                            => 'card',
            default                           => 'common',
        };
    }
}
