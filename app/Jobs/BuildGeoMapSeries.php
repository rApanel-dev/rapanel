<?php

namespace App\Jobs;

use App\Models\GameAccount;
use App\Services\GeoLocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildGeoMapSeries implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 300;

    public function handle(GeoLocationService $geo): void
    {
        $ips = GameAccount::whereNotNull('last_ip')
            ->where('last_ip', '!=', '')
            ->pluck('last_ip')
            ->unique()
            ->values();

        // Full HTTP resolution for all IPs, then rebuild map from results
        foreach ($ips as $ip) {
            $geo->resolveIp($ip);
        }

        // Rebuild map series from now-populated per-IP caches (no more HTTP)
        $geo->forgetMapCache('admin_geo_map_series');
        $geo->buildMapSeriesFromCache($ips, 'admin_geo_map_series');
    }
}
