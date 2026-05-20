<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsComment;
use Illuminate\Http\Request;

class NewsCommentController extends Controller
{
    public function store(Request $request, News $news)
    {
        abort_unless($news->is_published && $news->allow_comments, 403);

        $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        NewsComment::create([
            'news_id'     => $news->id,
            'user_id'     => $request->user()->id,
            'body'        => $request->body,
            'is_approved' => true,
        ]);

        return back()->with('success', __('Comment posted successfully.'));
    }
}
