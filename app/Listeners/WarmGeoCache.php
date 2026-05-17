<?php

namespace App\Listeners;

use App\Services\GeoLocationService;
use Illuminate\Auth\Events\Login;

class WarmGeoCache
{
    public function __construct(private GeoLocationService $geo) {}

    public function handle(Login $event): void
    {
        // Invalidate the map cache so the next admin dashboard load reflects the latest data.
        // With MaxMind (local .mmdb), the rebuild on the next request takes ~350ms — no queue needed.
        $this->geo->forgetMapCache('admin_geo_map_series');
    }
}
