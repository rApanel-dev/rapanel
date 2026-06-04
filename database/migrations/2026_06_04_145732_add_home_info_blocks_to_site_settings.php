<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $infoBlocks = json_encode([
            ['id' => 'rates',   'show' => true, 'icon_type' => 'icon', 'image' => null],
            ['id' => 'level',   'show' => true, 'icon_type' => 'icon', 'image' => null],
            ['id' => 'mode',    'show' => true, 'icon_type' => 'icon', 'image' => null],
            ['id' => 'episode', 'show' => true, 'icon_type' => 'icon', 'image' => null],
            ['id' => 'intl',    'show' => true, 'icon_type' => 'icon', 'image' => null],
        ]);

        $highlightCards = json_encode([
            ['show' => true, 'image' => null, 'title' => 'New Battlegrounds', 'desc' => 'New Battleground modes designed to improve GvG and competitive gameplay, not found on other servers.', 'url' => '#'],
            ['show' => true, 'image' => null, 'title' => 'Custom Events',     'desc' => 'Regular in-game events with exclusive prizes and unique rewards for all players.',                   'url' => '#'],
            ['show' => true, 'image' => null, 'title' => 'Exclusive Quests',  'desc' => 'Unique quest lines with story-driven content and rare item rewards to discover.',                    'url' => '#'],
            ['show' => true, 'image' => null, 'title' => 'Item Database',     'desc' => 'Full searchable database of every item imported directly from rAthena data files.',                  'url' => '#'],
        ]);

        $now = now();
        foreach (['home_info_blocks' => $infoBlocks, 'home_highlight_cards' => $highlightCards] as $key => $value) {
            SiteSetting::upsert(
                [['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]],
                ['key'],
                ['value']
            );
        }
    }

    public function down(): void
    {
        SiteSetting::whereIn('key', ['home_info_blocks', 'home_highlight_cards'])->delete();
    }
};
