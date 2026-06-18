<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        $defaults = [
            // General
            'site_name'          => env('RA_SERVER_NAME', 'rApanel'),
            'logo_light'         => null,
            'logo_dark'          => null,
            'discord_server_id'  => env('DISCORD_SERVER_ID', ''),
            'discord_invite_url' => env('DISCORD_INVITE_URL', ''),

            // Home
            'home_base_rate'       => '100',
            'home_job_rate'        => '100',
            'home_max_base_level'  => '99',
            'home_max_job_level'   => '70',
            'home_episode'         => 'Episode 13.3+ — El Dicastes',
            'home_about_text'      => '<p>Welcome to our Ragnarok Online server. We offer a unique gaming experience with a dedicated community and regular updates. Join us and start your adventure today!</p>',
            'home_community_text'  => '<p>Join our growing community of adventurers. Connect with other players, share your experiences, and become part of our story.</p>',

            // SEO
            'seo_title'       => env('RA_SERVER_NAME', 'rApanel'),
            'seo_description' => 'rApanel — Panel de control para servidores Ragnarok Online rAthena/Hercules.',
            'seo_og_image'    => null,
            'seo_theme_color' => '#3b82f6',
            'favicon'         => null,

            // Home — toggles de secciones
            'home_show_stats'    => '1',
            'home_show_info'     => '1',
            'home_show_news'     => '1',
            'home_show_features' => '1',
            'home_show_cta'      => '1',

            // Home — bloques de info y cards destacadas
            'home_info_blocks' => json_encode([
                ['id' => 'rates',   'show' => true, 'icon_type' => 'icon', 'image' => null],
                ['id' => 'level',   'show' => true, 'icon_type' => 'icon', 'image' => null],
                ['id' => 'mode',    'show' => true, 'icon_type' => 'icon', 'image' => null],
                ['id' => 'episode', 'show' => true, 'icon_type' => 'icon', 'image' => null],
                ['id' => 'intl',    'show' => true, 'icon_type' => 'icon', 'image' => null],
            ]),
            'home_highlight_cards' => json_encode([
                ['show' => true, 'image' => null, 'title' => 'New Battlegrounds', 'desc' => 'New Battleground modes designed to improve GvG and competitive gameplay, not found on other servers.', 'url' => '#'],
                ['show' => true, 'image' => null, 'title' => 'Custom Events',     'desc' => 'Regular in-game events with exclusive prizes and unique rewards for all players.',                   'url' => '#'],
                ['show' => true, 'image' => null, 'title' => 'Exclusive Quests',  'desc' => 'Unique quest lines with story-driven content and rare item rewards to discover.',                    'url' => '#'],
                ['show' => true, 'image' => null, 'title' => 'Item Database',     'desc' => 'Full searchable database of every item imported directly from rAthena data files.',                  'url' => '#'],
            ]),
        ];

        $now = now();
        foreach ($defaults as $key => $value) {
            SiteSetting::insert([
                'key'        => $key,
                'value'      => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
