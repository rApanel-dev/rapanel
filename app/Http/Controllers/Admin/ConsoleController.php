<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConsoleController extends Controller
{
    private array $logFiles = [
        'atcommand' => ['label' => 'AT Commands',  'file' => 'atcommand.log'],
        'chat'      => ['label' => 'Chat',          'file' => 'chat.log'],
        'map'       => ['label' => 'Map Server',    'file' => 'map.log'],
        'login'     => ['label' => 'Login Server',  'file' => 'login.log'],
        'char'      => ['label' => 'Char Server',   'file' => 'char.log'],
        'mvp'       => ['label' => 'MvP Kills',     'file' => 'mvp.log'],
        'zeny'      => ['label' => 'Zeny',          'file' => 'zeny.log'],
        'pick'      => ['label' => 'Pick / Drop',   'file' => 'pick.log'],
    ];

    public function index(Request $request)
    {
        $logPath     = config('services.ra.log_path');
        $selectedLog = $request->get('log', 'atcommand');
        $lines       = (int) $request->get('lines', 100);
        $lines       = max(10, min(500, $lines));

        $content       = null;
        $error         = null;
        $availableLogs = [];

        if ($logPath) {
            foreach ($this->logFiles as $key => $meta) {
                $path = rtrim($logPath, '/') . '/' . $meta['file'];
                if (file_exists($path) && is_readable($path)) {
                    $availableLogs[] = ['key' => $key, 'label' => $meta['label'], 'file' => $meta['file']];
                }
            }

            if (! isset($this->logFiles[$selectedLog])) {
                $selectedLog = array_key_first($this->logFiles);
            }

            $filename = $this->logFiles[$selectedLog]['file'];
            $path     = rtrim($logPath, '/') . '/' . $filename;

            if (file_exists($path) && is_readable($path)) {
                $content = $this->tailFile($path, $lines);
            } else {
                $error = "Log file not found or not readable: {$filename}";
            }
        } else {
            $error = 'RA_LOG_PATH is not configured. Add it to your .env file (e.g. RA_LOG_PATH=/path/to/rathena/log).';
        }

        return Inertia::render('Admin/Console', [
            'content'       => $content,
            'error'         => $error,
            'availableLogs' => $availableLogs,
            'selectedLog'   => $selectedLog,
            'lines'         => $lines,
        ]);
    }

    private function tailFile(string $path, int $n): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $total     = $file->key();
        $startLine = max(0, $total - $n);
        $file->seek($startLine);

        $result = [];
        while (! $file->eof()) {
            $result[] = rtrim($file->current());
            $file->next();
        }

        return implode("\n", array_filter($result, fn ($l) => $l !== ''));
    }
}
