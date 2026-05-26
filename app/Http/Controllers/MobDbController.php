<?php

namespace App\Http\Controllers;

use App\Models\ItemDb;
use App\Models\MobDb;
use App\Models\SpawnEntry;
use App\Services\DropRateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MobDbController extends Controller
{
    public function index(Request $request): Response
    {
        $search  = trim($request->input('search', ''));
        $filter  = $request->input('filter', '');
        $race    = $request->input('race', '');
        $element = $request->input('element', '');

        $mobs = MobDb::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                          ->orWhere('aegis_name', 'like', "%{$search}%")
                          ->orWhere('id', (int) $search ?: -1);
                });
            })
            ->when($filter === 'mvp',    fn ($q) => $q->where('is_mvp', true))
            ->when($filter === 'boss',   fn ($q) => $q->where('is_mvp', false)->where('class', 'Boss'))
            ->when($filter === 'normal', fn ($q) => $q->where('is_mvp', false)->where('class', '!=', 'Boss'))
            ->when($race    !== '',      fn ($q) => $q->where('race', $race))
            ->when($element !== '',      fn ($q) => $q->where('element', $element))
            ->select(['id', 'aegis_name', 'name', 'level', 'hp', 'is_mvp', 'element', 'race', 'size', 'class'])
            ->orderBy('id')
            ->paginate(40)
            ->withQueryString();

        $races    = MobDb::select('race')->distinct()->orderBy('race')->pluck('race')->toArray();
        $elements = MobDb::select('element')->distinct()->orderBy('element')->pluck('element')->toArray();

        return Inertia::render('Info/MobDb/Index', [
            'mobs'     => $mobs,
            'races'    => $races,
            'elements' => $elements,
            'filters'  => compact('search', 'filter', 'race', 'element'),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $mob  = MobDb::where('id', $id)->firstOrFail();
        $data = $mob->toArray();

        // Enrich drops with item_db data so the frontend can show icons + open ItemDbModal
        $aegisNames = collect($mob->drops ?? [])
            ->pluck('item')
            ->merge(collect($mob->mvp_drops ?? [])->pluck('item'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $itemLookup = ItemDb::whereIn('aegis_name', $aegisNames)
            ->select(['item_id', 'aegis_name', 'display_name', 'name', 'type', 'slots'])
            ->get()
            ->keyBy(fn ($i) => strtolower($i->aegis_name))
            ->toArray();

        $isMvp  = (bool) $mob->is_mvp;
        $isBoss = !$isMvp && $mob->class === 'Boss';

        $enrich = fn ($drops, bool $isMvpDrop = false) => collect($drops)->map(fn ($d) => array_merge($d, [
            'item_data'     => $itemLookup[strtolower($d['item'] ?? '')] ?? null,
            'adjusted_rate' => isset($d['rate'])
                ? DropRateCalculator::calculate(
                    (int) $d['rate'],
                    $itemLookup[strtolower($d['item'] ?? '')]['type'] ?? 'Etc',
                    $isMvp,
                    $isBoss,
                    $isMvpDrop
                )
                : null,
        ]))->values()->toArray();

        $data['drops']     = $enrich($mob->drops     ?? [], false);
        $data['mvp_drops'] = $enrich($mob->mvp_drops ?? [], true);

        $data['spawns'] = SpawnEntry::where('mob_id', $id)
            ->select(
                'map_name',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as spawn_points'),
                DB::raw('MIN(delay1) as min_delay'),
                DB::raw('SUM(CASE WHEN x = 0 AND y = 0 AND rx = 0 AND ry = 0 THEN amount ELSE 0 END) as random_amount'),
                DB::raw('SUM(CASE WHEN x != 0 OR y != 0 OR rx != 0 OR ry != 0 THEN 1 ELSE 0 END) as placed_points')
            )
            ->groupBy('map_name')
            ->orderBy('map_name')
            ->get()
            ->toArray();

        return response()->json($data);
    }
}
