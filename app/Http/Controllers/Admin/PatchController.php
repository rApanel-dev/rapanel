<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PatchController extends Controller
{
    /** Extensiones de archivo de parche aceptadas para subir. */
    private const ALLOWED_EXT = ['gpf', 'rgz', 'zip', '7z', 'grf', 'thor'];

    /** Tamaño máximo de subida en KB (512 MB) — ajustar PHP/nginx en consecuencia. */
    private const MAX_UPLOAD_KB = 524288;

    private function disk()
    {
        return Storage::disk('patches');
    }

    private function listFilename(): string
    {
        $name = (string) SiteSetting::getValue('patch_list_filename', 'patch_main.txt');
        // Saneo defensivo por si el setting quedó vacío o inválido.
        return preg_match('/^[A-Za-z0-9._-]+\.txt$/', $name) ? $name : 'patch_main.txt';
    }

    public function index(): Response
    {
        $disk = $this->disk();
        $listFile = $this->listFilename();

        $files = [];
        foreach ($disk->files() as $path) {
            $name = basename($path);
            if (in_array($name, ['.gitkeep', '.gitignore', $listFile], true)) continue;
            if (str_ends_with($name, '.bak')) continue;
            $files[] = [
                'name'         => $name,
                'size'         => $disk->size($path),
                'url'          => '/downloads/patch/' . rawurlencode($name),
                'modified_at'  => date('Y-m-d H:i', $disk->lastModified($path)),
            ];
        }
        // Más recientes primero.
        usort($files, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        $listContent = $disk->exists($listFile) ? $disk->get($listFile) : '';

        return Inertia::render('Admin/Patches/Index', [
            'files'         => $files,
            'listContent'   => $listContent,
            'listFilename'  => $listFile,
            'settings'      => [
                'patch_list_filename' => $listFile,
                'patcher_type'        => (string) SiteSetting::getValue('patcher_type', 'elurair'),
            ],
            'publicBaseUrl' => rtrim(config('app.url'), '/') . '/downloads/patch',
            'maxUploadMb'   => (int) (self::MAX_UPLOAD_KB / 1024),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:' . self::MAX_UPLOAD_KB,
        ]);

        $uploaded = $request->file('file');
        $ext = strtolower($uploaded->getClientOriginalExtension());

        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            return back()->with('error', __('Unsupported patch file type (.:ext). Allowed: :allowed', [
                'ext'     => $ext,
                'allowed' => implode(', ', self::ALLOWED_EXT),
            ]));
        }

        // Saneo del nombre: solo basename + caracteres seguros.
        $original = $uploaded->getClientOriginalName();
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($original));
        if ($safe === '' || $safe === '.' || $safe === '..') {
            return back()->with('error', __('Invalid file name.'));
        }

        $this->disk()->putFileAs('', $uploaded, $safe);

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'ADMIN',
            'action'     => 'patch_file_uploaded',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['file' => $safe],
        ]);

        return back()->with('success', __('Patch file uploaded: :file', ['file' => $safe]));
    }

    public function renameFile(Request $request): RedirectResponse
    {
        $request->validate([
            'old' => 'required|string|max:255',
            'new' => 'required|string|max:255',
        ]);

        $disk = $this->disk();
        $old = basename($request->old);
        $new = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($request->new));

        $protected = ['.gitkeep', '.gitignore', $this->listFilename()];
        if (in_array($old, $protected, true) || in_array($new, $protected, true)) {
            return back()->with('error', __('This file cannot be renamed here.'));
        }
        if ($new === '' || $new === '.' || $new === '..') {
            return back()->with('error', __('Invalid file name.'));
        }
        if (! $disk->exists($old)) {
            return back()->with('error', __('File not found.'));
        }

        $ext = strtolower(pathinfo($new, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            return back()->with('error', __('Unsupported patch file type (.:ext). Allowed: :allowed', [
                'ext'     => $ext,
                'allowed' => implode(', ', self::ALLOWED_EXT),
            ]));
        }
        if ($new !== $old && $disk->exists($new)) {
            return back()->with('error', __('A file with that name already exists.'));
        }
        if ($new === $old) {
            return back(); // sin cambios
        }

        $disk->move($old, $new);

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'ADMIN',
            'action'     => 'patch_file_renamed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['from' => $old, 'to' => $new],
        ]);

        return back()->with('success', __('Patch file renamed to :file', ['file' => $new]));
    }

    public function deleteFile(Request $request): RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:255']);

        $name = basename($request->name);
        $disk = $this->disk();

        // No permitir borrar archivos protegidos.
        if (in_array($name, ['.gitkeep', '.gitignore', $this->listFilename()], true)) {
            return back()->with('error', __('This file cannot be deleted here.'));
        }
        if (! $disk->exists($name)) {
            return back()->with('error', __('File not found.'));
        }

        $disk->delete($name);

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'ADMIN',
            'action'     => 'patch_file_deleted',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['file' => $name],
        ]);

        return back()->with('success', __('Patch file deleted: :file', ['file' => $name]));
    }

    public function saveList(Request $request): RedirectResponse
    {
        $request->validate([
            'content' => 'nullable|string|max:65535',
        ]);

        $disk = $this->disk();
        $listFile = $this->listFilename();
        $content = (string) $request->input('content', '');

        // Respaldo del contenido anterior antes de sobreescribir.
        if ($disk->exists($listFile)) {
            $disk->put($listFile . '.bak', $disk->get($listFile));
        }

        $disk->put($listFile, $content);

        ActionLog::create([
            'user_id'    => $request->user()->id,
            'category'   => 'ADMIN',
            'action'     => 'patch_list_saved',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => ['file' => $listFile, 'bytes' => strlen($content)],
        ]);

        return back()->with('success', __('Patch list saved (:file).', ['file' => $listFile]));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'patch_list_filename' => 'required|string|max:100|regex:/^[A-Za-z0-9._-]+\.txt$/',
            'patcher_type'        => 'required|string|in:elurair,thor,other',
        ]);

        SiteSetting::setMany([
            'patch_list_filename' => $request->patch_list_filename,
            'patcher_type'        => $request->patcher_type,
        ]);

        return back()->with('success', __('Patcher settings saved.'));
    }
}
