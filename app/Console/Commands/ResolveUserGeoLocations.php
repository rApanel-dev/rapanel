<?php

namespace App\Console\Commands;

use App\Models\GameAccount;
use App\Services\GeoLocationService;
use Illuminate\Console\Command;

class ResolveUserGeoLocations extends Command
{
    protected $signature   = 'geo:resolve-users {--force : Re-resolve IPs that are already cached}';
    protected $description = 'Pre-warm the geo-location cache for all registered user IPs';

    public function handle(GeoLocationService $geo): int
    {
        $ips = GameAccount::whereNotNull('last_ip')
            ->where('last_ip', '!=', '')
            ->pluck('last_ip')
            ->unique()
            ->values();

        if ($ips->isEmpty()) {
            $this->info('No IPs found.');
            return self::SUCCESS;
        }

        $this->info("Resolving {$ips->count()} unique IP(s)...");
        $bar = $this->output->createProgressBar($ips->count());
        $bar->start();

        $resolved = 0;

        foreach ($ips as $ip) {
            $country = $geo->resolveIp($ip);
            if ($country) {
                $resolved++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$resolved}/{$ips->count()} IPs resolved to a country.");

        $this->line('Building map series cache...');
        $allIps = GameAccount::whereNotNull('last_ip')->where('last_ip', '!=', '')->pluck('last_ip');
        $geo->forgetMapCache('admin_geo_map_series');
        $series = $geo->buildMapSeries($allIps, 'admin_geo_map_series');
        $this->info('Map series cached with ' . count($series) . ' countries.');

        return self::SUCCESS;
    }
}
