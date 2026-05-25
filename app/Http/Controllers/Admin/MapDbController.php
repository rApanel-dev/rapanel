<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapSize;
use App\Models\SpawnEntry;
use App\Services\MapSpawnParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapDbController extends Controller
{
    public function index(Request $request): Response
    {
        $stats = [
            'maps'         => MapSize::count(),
            'spawn_maps'   => SpawnEntry::distinct('map_name')->count('map_name'),
            'total_spawns' => SpawnEntry::count(),
            'last_sync'    => SpawnEntry::max('updated_at'),
        ];

        $raw          = config('services.ra.game_mode', 'renewal');
        $serverConfig = [
            'emulator'    => config('services.ra.emulator', 'rathena'),
            'server_mode' => str_starts_with($raw, 'pre') ? 'Pre-Renewal' : 'Renewal',
            'is_pre'      => str_starts_with($raw, 'pre'),
        ];

        $allowedSorts = ['map_name', 'display_name', 'width', 'updated_at', 'spawns_count', 'spawns_max_updated_at'];
        $sort      = in_array($request->sort, $allowedSorts) ? $request->sort : 'map_name';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $mapList = MapSize::withCount('spawns')
            ->withMax('spawns', 'updated_at')
            ->when($request->search, function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn($q2) => $q2->where('map_name', 'like', $term)
                                        ->orWhere('display_name', 'like', $term));
            })
            ->orderBy($sort, $direction)
            ->paginate(40)
            ->withQueryString();

        return Inertia::render('Admin/MapDb/Index', compact('stats', 'serverConfig', 'mapList', 'sort', 'direction'));
    }

    public function importMapCache(Request $request): RedirectResponse
    {
        $request->validate([
            'map_cache' => 'required|file|max:102400',
        ]);

        set_time_limit(0);

        $filePath = $request->file('map_cache')->getRealPath();
        $raw      = file_get_contents($filePath);
        $expected = (strlen($raw) >= 6)
            ? (int) unpack('x4/vmap_count', substr($raw, 0, 6))['map_count']
            : 0;

        $parser = new MapSpawnParser();
        $maps   = $parser->parseMapCache($filePath);

        if (empty($maps)) {
            return back()->withErrors(['map_cache' => __('Could not parse map_cache.dat. Make sure it is a valid rAthena map cache file.')]);
        }

        $now  = now()->toDateTimeString();
        $rows = [];

        foreach ($maps as $mapName => $dims) {
            $rows[] = [
                'map_name'   => $mapName,
                'width'      => $dims['width'],
                'height'     => $dims['height'],
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            MapSize::upsert($chunk, ['map_name'], ['width', 'height', 'updated_at']);
        }

        return back()->with([
            'success'       => __('Map cache imported successfully.'),
            'maps_imported' => count($maps),
            'maps_expected' => $expected,
        ]);
    }

    public function importSpawns(Request $request): RedirectResponse
    {
        $request->validate([
            'spawn_files'   => 'required|array|min:1',
            'spawn_files.*' => 'file|max:10240',
        ]);

        set_time_limit(0);

        $parser    = new MapSpawnParser();
        $filePaths = array_map(fn($f) => $f->getRealPath(), $request->file('spawn_files'));
        $debug     = $parser->parseSpawnFilesDebug($filePaths);
        $spawns    = $debug['spawns'];
        $skipped   = $debug['skipped'];

        if (empty($spawns)) {
            return back()->withErrors(['spawn_files' => __('No spawn entries found in the uploaded files.')])
                ->with('skipped_lines', $skipped);
        }

        $affectedMaps = array_unique(array_column($spawns, 'map_name'));
        SpawnEntry::whereIn('map_name', $affectedMaps)->delete();

        $now      = now()->toDateTimeString();
        $imported = 0;
        $failed   = 0;

        // Build per-map counts for the success banner
        $perMap = [];
        foreach ($spawns as $s) {
            $perMap[$s['map_name']] = ($perMap[$s['map_name']] ?? 0) + 1;
        }
        arsort($perMap);

        foreach (array_chunk($spawns, 500) as $chunk) {
            $rows = array_map(fn($s) => array_merge($s, [
                'updated_at' => $now,
                'created_at' => $now,
            ]), $chunk);

            try {
                SpawnEntry::insert($rows);
                $imported += count($rows);
            } catch (\Throwable) {
                $failed += count($rows);
            }
        }

        // maps_detail: top 30 maps sorted by spawn count (to keep flash small)
        $mapsDetail = array_slice(
            array_map(fn($name, $cnt) => ['name' => $name, 'count' => $cnt], array_keys($perMap), $perMap),
            0, 30
        );

        return back()->with([
            'success'         => __('Spawns imported successfully.'),
            'spawns_imported' => $imported,
            'spawns_failed'   => $failed,
            'maps_affected'   => count($affectedMaps),
            'maps_detail'     => $mapsDetail,
            'skipped_lines'   => $skipped,
        ]);
    }

    public function importMapInfo(Request $request): RedirectResponse
    {
        $request->validate([
            'map_info' => 'required|file|max:20480|mimes:lua,txt',
        ]);

        set_time_limit(0);

        $content = file_get_contents($request->file('map_info')->getRealPath());

        if (empty($content)) {
            return back()->withErrors(['map_info' => __('The file is empty or could not be read.')]);
        }

        // Match each map entry block (handles one level of nesting for signName)
        preg_match_all(
            '/\["([^"]+)\.rsw"\]\s*=\s*\{(?:[^{}]|\{[^{}]*\})*\}/s',
            $content,
            $blocks,
            PREG_SET_ORDER
        );

        if (empty($blocks)) {
            return back()->withErrors(['map_info' => __('No map entries found. Make sure this is a valid mapInfo.lua file.')]);
        }

        $rows    = [];
        $matched = 0;

        foreach ($blocks as $block) {
            $mapName = $block[1]; // key without .rsw
            $body    = $block[0]; // full match for field extraction

            preg_match('/displayName\s*=\s*"([^"]*)"/', $body, $dn);
            preg_match('/backgroundBmp\s*=\s*"([^"]*)"/', $body, $bg);

            $displayName   = isset($dn[1]) && $dn[1] !== '' ? $dn[1] : null;
            $backgroundBmp = isset($bg[1]) && $bg[1] !== '' ? $bg[1] : null;

            if ($displayName === null && $backgroundBmp === null) {
                continue;
            }

            $rows[$mapName] = [
                'display_name'   => $displayName,
                'background_bmp' => $backgroundBmp,
            ];
            $matched++;
        }

        if (empty($rows)) {
            return back()->withErrors(['map_info' => __('No display names found in the file.')]);
        }

        // Only update maps already in map_sizes
        $updated = 0;
        foreach (array_chunk($rows, 500, true) as $chunk) {
            $updated += MapSize::whereIn('map_name', array_keys($chunk))
                ->get(['map_name'])
                ->each(function ($map) use ($chunk) {
                    $map->update($chunk[$map->map_name]);
                })
                ->count();
        }

        return back()->with([
            'success'       => __('Map info imported successfully.'),
            'maps_matched'  => $updated,
            'maps_parsed'   => $matched,
        ]);
    }

    public function destroyMapSpawns(string $mapName): RedirectResponse
    {
        $count = SpawnEntry::where('map_name', $mapName)->count();
        SpawnEntry::where('map_name', $mapName)->delete();
        return back()->with('success', __(':count spawn entries deleted for :map.', ['count' => $count, 'map' => $mapName]));
    }

    public function destroyMapCache(): RedirectResponse
    {
        $count = MapSize::count();
        MapSize::truncate();
        return back()->with('success', __(':count maps deleted.', ['count' => $count]));
    }

    public function destroySpawns(): RedirectResponse
    {
        $count = SpawnEntry::count();
        SpawnEntry::truncate();
        return back()->with('success', __(':count spawn entries deleted.', ['count' => $count]));
    }
}
