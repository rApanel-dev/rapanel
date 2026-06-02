<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWikiArticleRequest;
use App\Http\Requests\UpdateWikiArticleRequest;
use App\Models\WikiArticle;
use App\Models\WikiSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WikiArticleController extends Controller
{
    public function index(): Response
    {
        $sectionId = request('section_id');

        $articles = WikiArticle::with('section', 'creator', 'updater')
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn ($a) => [
                'id'              => $a->id,
                'title'           => $a->title,
                'slug'            => $a->slug,
                'section_id'      => $a->section_id,
                'section_title'   => $a->section?->title,
                'section_icon'    => $a->section?->icon,
                'sort_order'      => $a->sort_order,
                'is_published'    => $a->is_published,
                'created_at'      => $a->created_at?->format('Y-m-d H:i'),
                'created_by_name' => $a->creator?->name,
                'updated_by_name' => $a->updater?->name,
            ]);

        $sections = WikiSection::orderBy('sort_order')->orderBy('id')
            ->get(['id', 'title', 'icon'])
            ->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'icon' => $s->icon]);

        return Inertia::render('Admin/Wiki/Articles/Index', [
            'articles'         => $articles,
            'sections'         => $sections,
            'filters'          => ['section_id' => $sectionId],
        ]);
    }

    public function create(): Response
    {
        $sections = WikiSection::orderBy('sort_order')->orderBy('id')
            ->get(['id', 'title', 'icon'])
            ->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'icon' => $s->icon]);

        return Inertia::render('Admin/Wiki/Articles/Create', compact('sections'));
    }

    public function store(StoreWikiArticleRequest $request): RedirectResponse
    {
        $imagePath = null;

        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('wiki', 'public');
        }

        WikiArticle::create([
            'section_id'     => $request->section_id,
            'title'          => $request->title,
            'slug'           => Str::slug($request->title . '-' . now()->timestamp),
            'content'        => $request->content,
            'featured_image' => $imagePath,
            'focal_x'        => (int) ($request->focal_x ?? 50),
            'focal_y'        => (int) ($request->focal_y ?? 50),
            'show_toc'       => (bool) ($request->show_toc ?? true),
            'sort_order'     => (int) ($request->sort_order ?? 0),
            'is_published'   => (bool) $request->is_published,
            'created_by'     => $request->user()->id,
        ]);

        return redirect()->route('admin.wiki.articles.index')
            ->with('success', __('Wiki article created successfully.'));
    }

    public function edit(WikiArticle $article): Response
    {
        $sections = WikiSection::orderBy('sort_order')->orderBy('id')
            ->get(['id', 'title', 'icon'])
            ->map(fn ($s) => ['id' => $s->id, 'title' => $s->title, 'icon' => $s->icon]);

        return Inertia::render('Admin/Wiki/Articles/Edit', [
            'article'  => array_merge($article->toArray(), [
                'featured_image_url' => $article->featured_image ? asset('storage/' . $article->featured_image) : null,
            ]),
            'sections' => $sections,
        ]);
    }

    public function update(UpdateWikiArticleRequest $request, WikiArticle $article): RedirectResponse
    {
        $article->section_id   = $request->section_id;
        $article->title        = $request->title;
        $article->content      = $request->content;
        $article->focal_x      = (int) ($request->focal_x ?? 50);
        $article->focal_y      = (int) ($request->focal_y ?? 50);
        $article->show_toc     = (bool) ($request->show_toc ?? true);
        $article->sort_order   = (int) ($request->sort_order ?? 0);
        $article->is_published = (bool) $request->is_published;
        $article->updated_by   = $request->user()->id;

        if ($request->hasFile('featured_image')) {
            $article->featured_image = $request->file('featured_image')->store('wiki', 'public');
        }

        $article->save();

        return redirect()->route('admin.wiki.articles.index')
            ->with('success', __('Wiki article updated successfully.'));
    }

    public function destroy(WikiArticle $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.wiki.articles.index')
            ->with('success', __('Wiki article deleted successfully.'));
    }
}
