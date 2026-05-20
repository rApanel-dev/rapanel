<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsComment;

class NewsCommentController extends Controller
{
    public function destroy(NewsComment $newsComment)
    {
        $newsComment->delete();

        return back()->with('success', __('Comment deleted successfully.'));
    }
}
