<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsReaction;
use Illuminate\Http\Request;

class NewsReactionController extends Controller
{
    public function toggle(Request $request, News $news)
    {
        abort_unless($news->is_published, 404);

        $userId = $request->user()->id;

        $existing = NewsReaction::where('news_id', $news->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            NewsReaction::create(['news_id' => $news->id, 'user_id' => $userId]);
        }

        return back();
    }
}
