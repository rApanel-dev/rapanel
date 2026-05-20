<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DownloadCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = DownloadCategory::withCount('downloads')
            ->with('updater')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'name'            => $c->name,
                'icon'            => $c->icon,
                'description'     => $c->description,
                'sort_order'      => $c->sort_order,
                'is_active'       => $c->is_active,
                'downloads_count' => $c->downloads_count,
                'created_at'      => $c->created_at?->format('Y-m-d H:i'),
                'created_ago'     => $c->created_at?->diffForHumans(),
                'updated_at'      => $c->updated_by ? $c->updated_at?->format('Y-m-d H:i') : null,
                'updated_ago'     => $c->updated_by ? $c->updated_at?->diffForHumans() : null,
                'updated_by_name' => $c->updater?->name,
            ]);

        return Inertia::render('Admin/Downloads/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Downloads/Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:download_categories,name',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:10',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        DownloadCategory::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'icon'        => $request->icon,
            'sort_order'  => $request->sort_order ?? 0,
            'is_active'   => (bool) $request->is_active,
        ]);

        return redirect()->route('admin.download-categories.index')
            ->with('success', __('Category created successfully.'));
    }

    public function edit(DownloadCategory $downloadCategory): Response
    {
        return Inertia::render('Admin/Downloads/Categories/Edit', [
            'category' => $downloadCategory,
        ]);
    }

    public function update(Request $request, DownloadCategory $downloadCategory): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:download_categories,name,' . $downloadCategory->id,
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:10',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $downloadCategory->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'icon'        => $request->icon,
            'sort_order'  => $request->sort_order ?? 0,
            'is_active'   => (bool) $request->is_active,
            'updated_by'  => $request->user()->id,
        ]);

        return redirect()->route('admin.download-categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    public function destroy(DownloadCategory $downloadCategory): RedirectResponse
    {
        $downloadCategory->delete();

        return redirect()->route('admin.download-categories.index')
            ->with('success', __('Category deleted successfully.'));
    }
}
