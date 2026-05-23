<?php

namespace App\Http\Controllers;

use App\Models\ItemDb;
use App\Models\MobDb;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ->select(['item_id', 'aegis_name', 'display_name', 'name'])
            ->get()
            ->keyBy(fn ($i) => strtolower($i->aegis_name))
            ->toArray();

        $enrich = fn ($drops) => collect($drops)->map(fn ($d) => array_merge($d, [
            'item_data' => $itemLookup[strtolower($d['item'] ?? '')] ?? null,
        ]))->values()->toArray();

        $data['drops']     = $enrich($mob->drops     ?? []);
        $data['mvp_drops'] = $enrich($mob->mvp_drops ?? []);

        return response()->json($data);
    }
}
