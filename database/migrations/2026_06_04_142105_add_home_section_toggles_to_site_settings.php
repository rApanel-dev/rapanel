<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'home_show_stats'    => '1',
            'home_show_info'     => '1',
            'home_show_news'     => '1',
            'home_show_features' => '1',
            'home_show_cta'      => '1',
        ];

        $now = now();
        foreach ($defaults as $key => $value) {
            SiteSetting::upsert(
                [['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]],
                ['key'],
                ['value']
            );
        }
    }

    public function down(): void
    {
        SiteSetting::whereIn('key', [
            'home_show_stats',
            'home_show_info',
            'home_show_news',
            'home_show_features',
            'home_show_cta',
        ])->delete();
    }
};
