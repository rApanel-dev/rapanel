<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function show(News $news): Response
    {
        abort_unless($news->is_published, 404);

        return Inertia::render('News/Show', [
            'news' => [
                'id'             => $news->id,
                'title'          => $news->title,
                'body'           => $news->body,
                'type'           => $news->type,
                'type_label'     => News::typeLabel($news->type),
                'featured_image' => $news->featured_image ? asset('storage/' . $news->featured_image) : null,
                'created_at'     => $news->created_at?->format('d M Y'),
                'created_ago'    => $news->created_at?->diffForHumans(),
            ],
        ]);
    }
}
