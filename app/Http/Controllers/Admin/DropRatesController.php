<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DropRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DropRatesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/DropRates/Index', [
            'dropRates' => DropRate::pluck('value', 'key')->toArray(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['drops_conf' => 'required|file|max:512']);

        $content = file_get_contents($request->file('drops_conf')->getRealPath());
        $parsed  = $this->parseDropsConf($content);

        DropRate::truncate();

        $rows = [];
        foreach ($parsed as $key => $value) {
            $rows[] = ['key' => $key, 'value' => $value];
        }
        if ($rows) {
            DropRate::insert($rows);
        }

        return back()->with('success', __(':count drop rate variables imported from drops.conf.', ['count' => count($parsed)]));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'drop_rates'   => 'nullable|array',
            'drop_rates.*' => 'nullable|integer',
        ]);

        $allowedPrefixes = ['item_rate_', 'item_drop_', 'drop_rate_cap'];
        $rows            = [];

        foreach ($request->drop_rates ?? [] as $key => $val) {
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $rows[] = ['key' => $key, 'value' => (int) $val];
                    break;
                }
            }
        }

        if ($rows) {
            DropRate::upsert($rows, ['key'], ['value']);
        }

        return back()->with('success', __('Drop rates saved.'));
    }

    public function clear(): RedirectResponse
    {
        DropRate::truncate();

        return back()->with('success', __('Drop rates cleared.'));
    }

    private function parseDropsConf(string $content): array
    {
        $result  = [];
        $pattern = '/^\s*(item_rate_\w+|item_drop_\w+|drop_rate_cap(?:_vip)?)\s*:\s*(\S+)/m';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $key           = trim($match[1]);
            $raw           = trim($match[2]);
            $result[$key]  = is_numeric($raw) ? (int) $raw : $raw;
        }

        return $result;
    }
}
