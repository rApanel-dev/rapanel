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
        $bgMap = [
            'dungeon' => fn($q) => $q->where('background_bmp', 'like', 'dungeon%'),
            'field'   => fn($q) => $q->where('background_bmp', 'like', 'field%'),
            'village' => fn($q) => $q->where('background_bmp', 'like', 'village%'),
            'siege'   => fn($q) => $q->where('background_bmp', 'siege'),
            'special' => fn($q) => $q->where('background_bmp', 'like', 'noname%'),
            'other'   => fn($q) => $q->whereNull('background_bmp'),
        ];

        $bgType = $request->bg_type;

        $maps = MapSize::withCount('spawns')
            ->when($request->search, function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn($q2) => $q2->where('map_name', 'like', $term)
                                        ->orWhere('display_name', 'like', $term));
            })
            ->when(isset($bgMap[$bgType]), $bgMap[$bgType] ?? fn($q) => $q)
            ->orderBy('map_name')
            ->paginate(48)
            ->withQueryString();

        return Inertia::render('Info/MapDb/Index', [
            'maps'    => $maps,
            'filters' => [
                'search'  => $request->search ?? '',
                'bg_type' => $bgType ?? '',
            ],
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
            'mapName'     => $mapName,
            'displayName' => $mapSize->display_name,
            'mapSize' => [
                'width'  => $mapSize->width,
                'height' => $mapSize->height,
            ],
            'spawns'  => $spawns,
        ]);
    }
}
