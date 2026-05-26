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
