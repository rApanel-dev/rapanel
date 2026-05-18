<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(): Response
    {
        $perPage = min((int) request('perPage', 10), 100);

        $news = News::orderByDesc('is_pinned')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn ($n) => [
                'id'            => $n->id,
                'title'         => $n->title,
                'slug'          => $n->slug,
                'type'          => $n->type,
                'type_label'    => News::typeLabel($n->type),
                'featured_image' => $n->featured_image ? asset('storage/' . $n->featured_image) : null,
                'is_published'  => $n->is_published,
                'is_pinned'     => $n->is_pinned,
                'created_at'    => $n->created_at?->diffForHumans(),
            ]);

        return Inertia::render('Admin/News/Index', [
            'news'    => $news,
            'filters' => request()->only(['perPage']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/News/Create');
    }

    public function store(StoreNewsRequest $request): RedirectResponse
    {
        $slug = Str::slug($request->title . '-' . now()->timestamp);
        $imagePath = null;

        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('news', 'public');
        }

        Cache::forget('home_latest_news');

        News::create([
            'title'          => $request->title,
            'slug'           => $slug,
            'body'           => $request->body,
            'featured_image' => $imagePath,
            'type'           => $request->type,
            'is_published'   => (bool) $request->is_published,
            'is_pinned'      => (bool) $request->is_pinned,
            'allow_comments' => (bool) $request->allow_comments,
            'created_by'     => $request->user()->id,
        ]);

        return redirect()->route('admin.news.index')
            ->with('success', __('News created successfully.'));
    }

    public function edit(News $news): Response
    {
        return Inertia::render('Admin/News/Edit', [
            'news' => array_merge($news->toArray(), [
                'featured_image_url' => $news->featured_image ? asset('storage/' . $news->featured_image) : null,
            ]),
        ]);
    }

    public function update(UpdateNewsRequest $request, News $news): RedirectResponse
    {
        $news->title       = $request->title;
        $news->slug        = $news->title !== $request->title
            ? Str::slug($request->title . '-' . now()->timestamp)
            : $news->slug;
        $news->body        = $request->body;
        $news->type        = $request->type;
        $news->is_published = (bool) $request->is_published;
        $news->is_pinned   = (bool) $request->is_pinned;
        $news->allow_comments = (bool) $request->allow_comments;
        $news->updated_by  = $request->user()->id;

        if ($request->hasFile('featured_image')) {
            $news->featured_image = $request->file('featured_image')->store('news', 'public');
        }

        $news->save();

        Cache::forget('home_latest_news');

        return redirect()->route('admin.news.edit', $news->id)
            ->with('success', __('News updated successfully.'));
    }

    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        Cache::forget('home_latest_news');

        return redirect()->route('admin.news.index')
            ->with('success', __('News deleted successfully.'));
    }
}
