<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Download;
use App\Models\DownloadCategory;
use App\Rules\SafeExternalUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DownloadController extends Controller
{
    public function index(): Response
    {
        $perPage = min((int) request('perPage', 15), 100);

        $downloads = Download::with('category', 'creator', 'updater')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn ($d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'slug'            => $d->slug,
                'category_name'   => $d->category?->name,
                'category_icon'   => $d->category?->icon,
                'is_external'     => $d->is_external,
                'is_only_auth'    => $d->is_only_auth,
                'is_active'       => $d->is_active,
                'download_count'  => $d->download_count,
                'created_by_name' => $d->creator?->name,
                'created_at'      => $d->created_at?->format('Y-m-d H:i'),
                'created_ago'     => $d->created_at?->diffForHumans(),
                'updated_at'      => $d->updated_by ? $d->updated_at?->format('Y-m-d H:i') : null,
                'updated_ago'     => $d->updated_by ? $d->updated_at?->diffForHumans() : null,
                'updated_by_name' => $d->updater?->name,
            ]);

        return Inertia::render('Admin/Downloads/Index', [
            'downloads' => $downloads,
            'filters'   => request()->only(['perPage']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Downloads/Create', [
            'categories' => DownloadCategory::where('is_active', true)
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'icon']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                   => 'required|string|max:200|unique:downloads,name',
            'category_id'            => 'nullable|exists:download_categories,id',
            'description'            => 'nullable|string',
            'image'                  => 'nullable|image|max:2048',
            'is_external'            => 'boolean',
            'is_external_url_hidden' => 'boolean',
            'file_url'               => ['required_if:is_external,true', 'nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i', new SafeExternalUrl],
            'file_name'              => 'nullable|string|max:255',
            'file'                   => 'required_if:is_external,false|nullable|file|max:524288', // 512MB
            'is_only_auth'           => 'boolean',
            'is_active'              => 'boolean',
            'sort_order'             => 'nullable|integer|min:0',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('downloads/images', 'public');
        }

        $filePath = null;
        $fileName = $request->file_name;
        if (! $request->is_external && $request->hasFile('file')) {
            $filePath = $request->file('file')->store('downloads/files', 'public');
            $fileName = $fileName ?: $request->file('file')->getClientOriginalName();
        }

        Download::create([
            'category_id'            => $request->category_id,
            'name'                   => $request->name,
            'slug'                   => Str::slug($request->name . '-' . now()->timestamp),
            'description'            => $request->description,
            'image_path'             => $imagePath,
            'is_external'            => (bool) $request->is_external,
            'is_external_url_hidden' => (bool) $request->is_external_url_hidden,
            'file_url'               => $request->file_url,
            'file_name'              => $fileName,
            'file_path'              => $filePath,
            'is_only_auth'           => (bool) $request->is_only_auth,
            'is_active'              => (bool) $request->is_active,
            'sort_order'             => $request->sort_order ?? 0,
            'created_by'             => $request->user()->id,
        ]);

        return redirect()->route('admin.downloads.index')
            ->with('success', __('Download created successfully.'));
    }

    public function edit(Download $download): Response
    {
        return Inertia::render('Admin/Downloads/Edit', [
            'download' => array_merge($download->toArray(), [
                'image_url' => $download->imageUrl(),
            ]),
            'categories' => DownloadCategory::where('is_active', true)
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'icon']),
        ]);
    }

    public function update(Request $request, Download $download): RedirectResponse
    {
        $request->validate([
            'name'                   => 'required|string|max:200|unique:downloads,name,' . $download->id,
            'category_id'            => 'nullable|exists:download_categories,id',
            'description'            => 'nullable|string',
            'image'                  => 'nullable|image|max:2048',
            'is_external'            => 'boolean',
            'is_external_url_hidden' => 'boolean',
            'file_url'               => ['required_if:is_external,true', 'nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i', new SafeExternalUrl],
            'file_name'              => 'nullable|string|max:255',
            'file'                   => 'nullable|file|max:524288',
            'is_only_auth'           => 'boolean',
            'is_active'              => 'boolean',
            'sort_order'             => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            if ($download->image_path) {
                Storage::disk('public')->delete($download->image_path);
            }
            $download->image_path = $request->file('image')->store('downloads/images', 'public');
        }

        if (! $request->is_external && $request->hasFile('file')) {
            if ($download->file_path) {
                Storage::disk('public')->delete($download->file_path);
            }
            $download->file_path = $request->file('file')->store('downloads/files', 'public');
            $download->file_name = $request->file_name ?: $request->file('file')->getClientOriginalName();
        } else {
            $download->file_name = $request->file_name ?: $download->file_name;
        }

        $download->category_id            = $request->category_id;
        $download->name                   = $request->name;
        $download->description            = $request->description;
        $download->is_external            = (bool) $request->is_external;
        $download->is_external_url_hidden = (bool) $request->is_external_url_hidden;
        $download->file_url               = $request->file_url;
        $download->is_only_auth           = (bool) $request->is_only_auth;
        $download->is_active              = (bool) $request->is_active;
        $download->sort_order             = $request->sort_order ?? 0;
        $download->updated_by             = $request->user()->id;
        $download->save();

        return redirect()->route('admin.downloads.index')
            ->with('success', __('Download updated successfully.'));
    }

    public function localFiles(): \Illuminate\Http\JsonResponse
    {
        $disk  = Storage::disk('public');
        $dir   = 'downloads/client';
        $files = [];

        if ($disk->exists($dir)) {
            foreach ($disk->files($dir) as $path) {
                $name = basename($path);
                if ($name === '.gitkeep') continue;
                $files[] = [
                    'name' => $name,
                    'size' => $disk->size($path),
                    'url'  => '/storage/' . $path,
                ];
            }
        }

        return response()->json($files);
    }

    public function destroy(Download $download): RedirectResponse
    {
        if ($download->image_path) {
            Storage::disk('public')->delete($download->image_path);
        }
        if ($download->file_path) {
            Storage::disk('public')->delete($download->file_path);
        }

        $download->delete();

        return redirect()->route('admin.downloads.index')
            ->with('success', __('Download deleted successfully.'));
    }
}
