<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemDb;
use App\Services\ItemDbParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemDbController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total'     => ItemDb::count(),
            'custom'    => ItemDb::where('is_custom', true)->count(),
            'last_sync' => ItemDb::max('updated_at'),
        ];

        $types = ItemDb::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        $raw          = config('services.ra.game_mode', 'renewal');
        $serverConfig = [
            'emulator'    => config('services.ra.emulator', 'rathena'),
            'server_mode' => str_starts_with($raw, 'pre') ? 'Pre-Renewal' : 'Renewal',
        ];

        return Inertia::render('Admin/ItemDb/Index', compact('stats', 'types', 'serverConfig'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'yml_files'   => 'required|array|min:1',
            'yml_files.*' => 'required|file|max:20480',
            'lua_info'    => 'nullable|file|max:20480',
        ]);

        set_time_limit(0);

        $parser  = new ItemDbParser();
        $luaInfo = [];

        if ($request->hasFile('lua_info')) {
            $luaInfo = $parser->parseLuaItemInfo(
                file_get_contents($request->file('lua_info')->getRealPath())
            );
        }

        // Parse all YAML files into a single map keyed by item_id
        $rows = [];
        foreach ($request->file('yml_files') as $file) {
            try {
                $items = $parser->parseYml(file_get_contents($file->getRealPath()));
            } catch (\RuntimeException) {
                continue;
            }

            foreach ($items as $item) {
                $mapped = $parser->mapItem($item, $luaInfo);
                if (empty($mapped)) {
                    continue;
                }

                $id = $mapped['item_id'];

                if (isset($rows[$id])) {
                    $rows[$id]['properties'] = array_merge(
                        $rows[$id]['properties'] ?? [],
                        $mapped['properties'] ?? [],
                    );
                } else {
                    $rows[$id] = $mapped;
                }
            }
        }

        // Upsert in chunks of 150
        $imported = 0;
        $updated  = 0;
        $failed   = 0;

        foreach (array_chunk(array_values($rows), 150) as $chunk) {
            try {
                $ids      = array_column($chunk, 'item_id');
                $existing = ItemDb::whereIn('item_id', $ids)->pluck('item_id')->flip()->all();

                $now      = now()->toDateTimeString();
                $toUpsert = array_map(function (array $row) use ($now) {
                    if (isset($row['properties']) && is_array($row['properties'])) {
                        $row['properties'] = json_encode($row['properties']);
                    }
                    $row['updated_at']  = $now;
                    $row['created_at'] ??= $now;
                    return $row;
                }, $chunk);

                ItemDb::upsert(
                    $toUpsert,
                    ['item_id'],
                    ['aegis_name', 'name', 'display_name', 'type', 'subtype',
                     'slots', 'description_html', 'properties', 'updated_at'],
                );

                foreach ($ids as $id) {
                    isset($existing[$id]) ? $updated++ : $imported++;
                }
            } catch (\Throwable) {
                $failed += count($chunk);
            }
        }

        return back()->with([
            'success'  => __('Import complete'),
            'imported' => $imported,
            'updated'  => $updated,
            'failed'   => $failed,
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $count = ItemDb::count();
        ItemDb::truncate();
        return back()->with('success', __(':count items deleted.', ['count' => $count]));
    }
}
