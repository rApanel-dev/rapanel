<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Manifest PWA dinámico. Se sirve en runtime desde ra_site_settings (editable en
 * Appearance → Brand) con fallback a los assets estáticos de /icons. Reemplaza al
 * antiguo public/manifest.json estático.
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $st = Cache::remember('ra_site_settings', 300, fn () => SiteSetting::pluck('value', 'key')->toArray());

        $val = fn (string $key, $default = null) => (isset($st[$key]) && $st[$key] !== '') ? $st[$key] : $default;

        $icon = function (string $key, string $fallback, string $sizes, string $purpose) use ($val): array {
            $stored = $val($key);
            return [
                'src'     => $stored ? '/storage/' . $stored : $fallback,
                'type'    => 'image/png',
                'sizes'   => $sizes,
                'purpose' => $purpose,
            ];
        };

        $manifest = [
            'name'             => $val('pwa_name') ?: $val('site_name', config('app.name', 'rApanel')),
            'short_name'       => $val('pwa_short_name') ?: $val('site_name', 'rApanel'),
            'description'      => $val('pwa_description', 'Panel de control para servidores Ragnarok Online rAthena/Hercules.'),
            'start_url'        => '/',
            'scope'            => '/',
            'display'          => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone'],
            'orientation'      => 'any',
            'lang'             => app()->getLocale(),
            'background_color' => $val('pwa_bg_color', '#0f172a'),
            'theme_color'      => $val('seo_theme_color', '#0f172a'),
            'categories'       => ['games', 'utilities'],
            'icons'            => [
                $icon('pwa_icon_192',          '/icons/icon-192x192.png',          '192x192', 'any'),
                $icon('pwa_icon_192_maskable', '/icons/icon-192x192-maskable.png', '192x192', 'maskable'),
                $icon('pwa_icon_512',          '/icons/icon-512x512.png',          '512x512', 'any'),
                $icon('pwa_icon_512_maskable', '/icons/icon-512x512-maskable.png', '512x512', 'maskable'),
            ],
            'screenshots' => [
                ['src' => '/images/web_share.jpg', 'type' => 'image/jpeg', 'sizes' => '1200x630', 'form_factor' => 'wide'],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
