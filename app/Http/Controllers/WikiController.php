<?php

namespace App\Http\Controllers;

use App\Models\WikiArticle;
use App\Models\WikiSection;
use Inertia\Inertia;
use Inertia\Response;

class WikiController extends Controller
{
    private function navSections(): array
    {
        return WikiSection::where('is_published', true)
            ->with(['articles' => fn ($q) => $q
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->select(['id', 'section_id', 'title', 'slug'])
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => [
                'id'       => $s->id,
                'title'    => $s->title,
                'slug'     => $s->slug,
                'icon'     => $s->icon,
                'articles' => $s->articles->map(fn ($a) => [
                    'title' => $a->title,
                    'slug'  => $a->slug,
                ])->toArray(),
            ])
            ->toArray();
    }

    public function index(): Response
    {
        return Inertia::render('Info/Wiki', [
            'navSections'    => $this->navSections(),
            'currentSection' => null,
            'currentArticle' => null,
        ]);
    }

    public function show(WikiSection $section, WikiArticle $article): Response
    {
        abort_if(! $section->is_published || ! $article->is_published, 404);
        abort_if($article->section_id !== $section->id, 404);

        $article->load('creator', 'updater');

        $siblings = WikiArticle::where('section_id', $section->id)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'slug']);

        $idx  = $siblings->search(fn ($a) => $a->id === $article->id);
        $prev = $idx > 0 ? $siblings->get($idx - 1)->only(['title', 'slug']) : null;
        $next = $idx < $siblings->count() - 1 ? $siblings->get($idx + 1)->only(['title', 'slug']) : null;

        return Inertia::render('Info/Wiki', [
            'navSections' => $this->navSections(),
            'currentSection' => [
                'id'    => $section->id,
                'title' => $section->title,
                'slug'  => $section->slug,
                'icon'  => $section->icon,
            ],
            'currentArticle' => [
                'id'             => $article->id,
                'title'          => $article->title,
                'slug'           => $article->slug,
                'content'        => $article->content,
                'featured_image' => $article->featured_image ? asset('storage/' . $article->featured_image) : null,
                'focal_x'        => $article->focal_x ?? 50,
                'focal_y'        => $article->focal_y ?? 50,
                'show_toc'       => $article->show_toc,
                'reading_time'   => $article->reading_time,
                'created_at'     => $article->created_at?->translatedFormat('d M Y'),
                'updated_at'     => $article->updated_at && $article->updated_by
                    ? $article->updated_at->translatedFormat('d M Y')
                    : null,
                'created_by'     => $article->creator?->name,
                'updated_by'     => $article->updater?->name,
                'prev'           => $prev,
                'next'           => $next,
            ],
        ]);
    }
}
