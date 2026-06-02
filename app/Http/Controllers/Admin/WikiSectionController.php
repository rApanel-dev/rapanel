<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWikiSectionRequest;
use App\Http\Requests\UpdateWikiSectionRequest;
use App\Models\WikiSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WikiSectionController extends Controller
{
    public function index(): Response
    {
        $sections = WikiSection::with('creator', 'updater')
            ->withCount('articles')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'title'          => $s->title,
                'slug'           => $s->slug,
                'icon'           => $s->icon,
                'description'    => $s->description,
                'sort_order'     => $s->sort_order,
                'is_published'   => $s->is_published,
                'articles_count' => $s->articles_count,
                'created_at'     => $s->created_at?->format('Y-m-d H:i'),
                'created_by_name'=> $s->creator?->name,
                'updated_by_name'=> $s->updater?->name,
            ]);

        return Inertia::render('Admin/Wiki/Sections/Index', compact('sections'));
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Wiki/Sections/Create');
    }

    public function store(StoreWikiSectionRequest $request): RedirectResponse
    {
        WikiSection::create([
            'title'        => $request->title,
            'slug'         => Str::slug($request->title . '-' . now()->timestamp),
            'description'  => $request->description,
            'icon'         => $request->icon ?: '📖',
            'sort_order'   => (int) ($request->sort_order ?? 0),
            'is_published' => (bool) $request->is_published,
            'created_by'   => $request->user()->id,
        ]);

        return redirect()->route('admin.wiki.sections.index')
            ->with('success', __('Wiki section created successfully.'));
    }

    public function edit(WikiSection $section): Response
    {
        return Inertia::render('Admin/Wiki/Sections/Edit', [
            'section' => $section->toArray(),
        ]);
    }

    public function update(UpdateWikiSectionRequest $request, WikiSection $section): RedirectResponse
    {
        $section->title        = $request->title;
        $section->description  = $request->description;
        $section->icon         = $request->icon ?: '📖';
        $section->sort_order   = (int) ($request->sort_order ?? 0);
        $section->is_published = (bool) $request->is_published;
        $section->updated_by   = $request->user()->id;
        $section->save();

        return redirect()->route('admin.wiki.sections.index')
            ->with('success', __('Wiki section updated successfully.'));
    }

    public function destroy(WikiSection $section): RedirectResponse
    {
        $section->delete();

        return redirect()->route('admin.wiki.sections.index')
            ->with('success', __('Wiki section deleted successfully.'));
    }
}
