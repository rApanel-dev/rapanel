<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\GameAccount;
use App\Models\User;
use App\Services\GeoLocationService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users'          => User::count(),
            'active_users'         => User::where('status', 1)->count(),
            'banned_users'         => User::where('status', 0)->count(),
            'admin_users'          => User::where('role', 'Admin')->count(),
            'total_game_accounts'  => GameAccount::count(),
            'active_game_accounts' => GameAccount::where('state', 0)->count(),
            'logs_today'           => ActionLog::whereDate('created_at', today())->count(),
            'logs_total'           => ActionLog::count(),
        ];

        $mapSeries = Cache::get('admin_geo_map_series');

        if ($mapSeries === null) {
            $geo  = app(GeoLocationService::class);
            $ips  = GameAccount::whereNotNull('last_ip')->where('last_ip', '!=', '')->pluck('last_ip');
            $mapSeries = $geo->buildMapSeries($ips, 'admin_geo_map_series');
        }

        $topCountries = collect($mapSeries)
            ->take(10)
            ->map(fn ($total, $code) => ['code' => $code, 'total' => $total])
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'stats'        => $stats,
            'mapSeries'    => $mapSeries,
            'topCountries' => $topCountries,
        ]);
    }
}
