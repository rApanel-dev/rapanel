<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class GeoLocationService
{
    /** Cache a single IP → country code for 30 days. */
    public function resolveIp(string $ip): ?string
    {
        if (! $this->isPublicIp($ip)) {
            return null;
        }

        $cacheKey = "geo_ip_{$ip}";

        // Check for an already-cached value (including previously-failed lookups stored as '')
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return $cached ?: null;
        }

        try {
            $position = Location::get($ip);
            $code     = $position?->countryCode ?: null;
        } catch (\Throwable) {
            $code = null;
        }

        // Cache success for 30 days; cache failures for 1 hour to avoid hammering the API
        if ($code) {
            Cache::put($cacheKey, $code, now()->addDays(30));
        } else {
            Cache::put($cacheKey, '', now()->addHour());
        }

        return $code;
    }

    /**
     * Build country → user count map from a list of IPs.
     * Caches the resolved map under the given cache key for $ttlMinutes.
     *
     * @param  iterable<string|null>  $ips
     * @return array<string,int>       e.g. ['CL' => 5, 'US' => 3]
     */
    public function buildMapSeries(iterable $ips, string $cacheKey, int $ttlMinutes = 60): array
    {
        return Cache::remember($cacheKey, now()->addMinutes($ttlMinutes), function () use ($ips) {
            $series = [];

            foreach ($ips as $ip) {
                if (! $ip) {
                    continue;
                }

                $country = $this->resolveIp($ip);

                if ($country) {
                    $series[$country] = ($series[$country] ?? 0) + 1;
                }
            }

            arsort($series);
            return $series;
        });
    }

    /**
     * Build map series using only already-cached per-IP results.
     * Makes no HTTP calls — safe to call synchronously in a web request.
     *
     * @param  iterable<string|null>  $ips
     * @return array<string,int>
     */
    public function buildMapSeriesFromCache(iterable $ips, string $cacheKey, int $ttlMinutes = 60): array
    {
        $series = [];

        foreach ($ips as $ip) {
            if (! $ip || ! $this->isPublicIp($ip)) {
                continue;
            }

            $country = Cache::get("geo_ip_{$ip}");

            if ($country) {
                $series[$country] = ($series[$country] ?? 0) + 1;
            }
        }

        arsort($series);

        if (! empty($series)) {
            Cache::put($cacheKey, $series, now()->addMinutes($ttlMinutes));
        }

        return $series;
    }

    /** Forget the cached map (call after new logins to refresh). */
    public function forgetMapCache(string $cacheKey): void
    {
        Cache::forget($cacheKey);
    }

    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
