<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\DownloadCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = DownloadCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        $query = Download::with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if (! $request->user()) {
            $query->where('is_only_auth', false);
        }

        $downloads = $query->get()->map(fn ($d) => [
            'id'             => $d->id,
            'name'           => $d->name,
            'slug'           => $d->slug,
            'description'    => $d->description,
            'image_url'      => $d->imageUrl(),
            'category_id'    => $d->category_id,
            'category_name'  => $d->category?->name,
            'category_icon'  => $d->category?->icon,
            'is_only_auth'   => $d->is_only_auth,
            'download_count' => $d->download_count,
            'file_name'      => $d->file_name ?? $d->name,
            'created_at'     => $d->created_at?->diffForHumans(),
        ]);

        return Inertia::render('Downloads/Index', [
            'downloads'  => $downloads,
            'categories' => $categories,
        ]);
    }

    public function show(Download $download): Response
    {
        abort_if(! $download->is_active, 404);

        if ($download->is_only_auth) {
            abort_if(! auth()->check(), 403);
        }

        return Inertia::render('Downloads/Show', [
            'download' => [
                'id'             => $download->id,
                'name'           => $download->name,
                'slug'           => $download->slug,
                'description'    => $download->description,
                'image_url'      => $download->imageUrl(),
                'category_name'  => $download->category?->name,
                'category_icon'  => $download->category?->icon,
                'category_slug'  => $download->category?->slug,
                'is_only_auth'   => $download->is_only_auth,
                'download_count' => $download->download_count,
                'file_name'      => $download->file_name ?? $download->name,
                'created_at'     => $download->created_at?->format('M d, Y'),
            ],
        ]);
    }

    public function download(Request $request, Download $download): mixed
    {
        abort_if(! $download->is_active, 404);

        if ($download->is_only_auth) {
            abort_if(! $request->user(), 403);
        }

        $download->increment('download_count');

        if (! $download->is_external) {
            if (! $download->file_path) {
                abort(404);
            }
            return response()->download(storage_path('app/public/' . $download->file_path), $download->file_name);
        }

        if ($download->is_external_url_hidden) {
            $fileName = $download->file_name ?? basename(parse_url($download->file_url, PHP_URL_PATH) ?? 'download');
            $response = Http::timeout(30)->get($download->file_url);

            abort_if(! $response->successful(), 502);

            $body        = $response->body();
            $contentType = $response->header('Content-Type') ?? 'application/octet-stream';

            return response()->streamDownload(function () use ($body) {
                echo $body;
            }, $fileName, ['Content-Type' => $contentType]);
        }

        return redirect($download->file_url);
    }
}
