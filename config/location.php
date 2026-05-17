<?php

use Stevebauman\Location\Drivers\GeoPlugin;
use Stevebauman\Location\Drivers\IpApi;
use Stevebauman\Location\Drivers\IpInfo;
use Stevebauman\Location\Drivers\MaxMind;
use Stevebauman\Location\Position;

/*
 * Map short driver names (MineTrax-style) → class names.
 * GEOLOCATION_DRIVER=ip_api             → IpApi::class   (free, no key)
 * GEOLOCATION_DRIVER=maxmind_database   → MaxMind::class (local .mmdb)
 * GEOLOCATION_DRIVER=maxmind_api        → MaxMind::class (web service)
 */
$driverMap = [
    'ip_api'           => IpApi::class,
    'ipinfo'           => IpInfo::class,
    'maxmind_database' => MaxMind::class,
    'maxmind_api'      => MaxMind::class,
];

$driverEnv = env('GEOLOCATION_DRIVER', 'ip_api');
$driver    = $driverMap[$driverEnv] ?? ($driverEnv ?: IpApi::class);

return [

    'driver' => $driver,

    'fallbacks' => [
        IpInfo::class,
        GeoPlugin::class,
    ],

    'position' => Position::class,

    'http' => [
        'timeout'         => 3,
        'connect_timeout' => 3,
    ],

    /*
    | In local/dev environments (127.0.0.1) the package cannot resolve the
    | real IP. Set LOCATION_TESTING=true and it will use the testing IP below.
    */
    'testing' => [
        'ip'      => env('LOCATION_TESTING_IP', '66.102.0.0'),
        'enabled' => env('LOCATION_TESTING', false),
    ],

    /*
    | MaxMind — used when GEOLOCATION_DRIVER=maxmind_database or maxmind_api
    |
    | maxmind_database: Download GeoLite2-Country.mmdb from MaxMind (free).
    |   php artisan location:update   ← downloads/updates the local database.
    |
    | maxmind_api: Uses the web service (requires user_id + license_key).
    */
    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),

        'web' => [
            'enabled' => $driverEnv === 'maxmind_api',
            'user_id' => env('MAXMIND_USER_ID'),
            'locales' => ['en'],
            'options' => ['host' => 'geoip.maxmind.com'],
        ],

        'local' => [
            'type' => 'country',
            'path' => database_path('maxmind/GeoLite2-Country.mmdb'),
            'url'  => sprintf(
                'https://download.maxmind.com/app/geoip_download_by_token?edition_id=GeoLite2-Country&license_key=%s&suffix=tar.gz',
                env('MAXMIND_LICENSE_KEY')
            ),
        ],
    ],

    'ip_api' => [
        'token' => env('IP_API_TOKEN'),
    ],

    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],

];
