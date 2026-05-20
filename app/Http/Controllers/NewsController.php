<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(): Response
    {
        $type = request('type');

        $news = News::where('is_published', true)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->through(fn ($n) => [
                'id'             => $n->id,
                'title'          => $n->title,
                'slug'           => $n->slug,
                'type'           => $n->type,
                'type_label'     => News::typeLabel($n->type),
                'featured_image' => $n->featured_image ? asset('storage/' . $n->featured_image) : null,
                'is_pinned'      => $n->is_pinned,
                'created_at'     => $n->created_at?->format('d M Y'),
                'created_ago'    => $n->created_at?->diffForHumans(),
            ]);

        return Inertia::render('News/Index', [
            'news'    => $news,
            'filters' => ['type' => $type],
        ]);
    }

    public function show(News $news): Response
    {
        abort_unless($news->is_published, 404);

        $news->load('creator', 'comments.user', 'reactions');

        $userId = auth()->id();

        $comments = $news->comments->map(fn ($c) => [
            'id'         => $c->id,
            'body'       => $c->body,
            'user_name'  => $c->user?->name,
            'created_at' => $c->created_at?->format('d M Y'),
            'created_ago'=> $c->created_at?->diffForHumans(),
        ])->values();

        return Inertia::render('News/Show', [
            'news' => [
                'id'               => $news->id,
                'slug'             => $news->slug,
                'title'            => $news->title,
                'body'             => $news->body,
                'type'             => $news->type,
                'type_label'       => News::typeLabel($news->type),
                'featured_image'   => $news->featured_image ? asset('storage/' . $news->featured_image) : null,
                'allow_comments'   => $news->allow_comments,
                'likes_count'      => $news->reactions->count(),
                'user_has_liked'   => $userId ? $news->reactions->contains('user_id', $userId) : false,
                'created_at'       => $news->created_at?->format('d M Y'),
                'created_ago'      => $news->created_at?->diffForHumans(),
                'created_by_name'  => $news->creator?->name,
            ],
            'comments' => $comments,
        ]);
    }
}
