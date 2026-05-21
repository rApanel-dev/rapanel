<?php

namespace App\Http\Controllers;

use App\Models\ItemDb;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemDbController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $type   = $request->input('type', '');

        $items = ItemDb::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('display_name', 'like', "%{$search}%")
                          ->orWhere('name', 'like', "%{$search}%")
                          ->orWhere('aegis_name', 'like', "%{$search}%")
                          ->orWhere('item_id', (int) $search ?: -1);
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->select([
                'id', 'item_id', 'aegis_name', 'name', 'display_name',
                'type', 'subtype', 'slots',
                'description_html', 'properties',
            ])
            ->orderBy('item_id')
            ->paginate(40)
            ->withQueryString();

        $types = ItemDb::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->toArray();

        return Inertia::render('ItemDb/Index', [
            'items'   => $items,
            'types'   => $types,
            'filters' => compact('search', 'type'),
        ]);
    }

    public function show(int $itemId): \Illuminate\Http\JsonResponse
    {
        $item = ItemDb::where('item_id', $itemId)->firstOrFail();

        return response()->json($item);
    }
}
