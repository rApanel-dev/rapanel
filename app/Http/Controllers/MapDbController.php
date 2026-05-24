<?php

namespace App\Http\Controllers;

use App\Models\MapSize;
use App\Models\MobDb;
use App\Models\SpawnEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapDbController extends Controller
{
    public function index(Request $request): Response
    {
        $mapsWithSpawns = SpawnEntry::select('map_name')->distinct()->pluck('map_name');

        $maps = MapSize::whereIn('map_name', $mapsWithSpawns)
            ->withCount('spawns')
            ->when(
                $request->search,
                fn($q) => $q->where('map_name', 'like', '%' . $request->search . '%')
            )
            ->orderBy('map_name')
            ->paginate(48)
            ->withQueryString();

        return Inertia::render('Info/MapDb/Index', [
            'maps'    => $maps,
            'filters' => ['search' => $request->search ?? ''],
        ]);
    }

    public function show(string $mapName): Response
    {
        $mapSize = MapSize::where('map_name', $mapName)->firstOrFail();

        $rawSpawns = SpawnEntry::where('map_name', $mapName)
            ->select('mob_id', 'mob_name', 'x', 'y', 'rx', 'ry', 'amount', 'delay1', 'delay2')
            ->orderBy('mob_id')
            ->get()
            ->toArray();

        $mobIds = array_unique(array_column($rawSpawns, 'mob_id'));
        $mobs   = MobDb::whereIn('id', $mobIds)
            ->select('id', 'name', 'level', 'hp', 'element', 'race', 'size', 'is_mvp', 'class')
            ->get()
            ->keyBy('id');

        $spawns = array_map(function ($spawn) use ($mobs) {
            $mob = $mobs[$spawn['mob_id']] ?? null;
            return [
                'mob_id'    => $spawn['mob_id'],
                'mob_name'  => $mob?->name ?? $spawn['mob_name'],
                'x'         => $spawn['x'],
                'y'         => $spawn['y'],
                'rx'        => $spawn['rx'],
                'ry'        => $spawn['ry'],
                'amount'    => $spawn['amount'],
                'delay1'    => $spawn['delay1'],
                'delay2'    => $spawn['delay2'],
                'is_mvp'    => $mob?->is_mvp ?? false,
                'mob_class' => $mob?->class ?? 'Normal',
                'level'     => $mob?->level ?? null,
                'hp'        => $mob?->hp ?? null,
                'element'   => $mob?->element ?? null,
                'race'      => $mob?->race ?? null,
                'size'      => $mob?->size ?? null,
            ];
        }, $rawSpawns);

        return Inertia::render('Info/MapDb/Show', [
            'mapName' => $mapName,
            'mapSize' => [
                'width'  => $mapSize->width,
                'height' => $mapSize->height,
            ],
            'spawns'  => $spawns,
        ]);
    }
}
