<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Personalización / Appearance (Fase 3 de PLAN_PERSONALIZACION.md).
 *
 * Persiste el tema (colores + imagen de fondo) en ra_site_settings:
 *   - clave `theme`           → JSON con buttons/light/dark
 *   - clave `theme_bg_image`  → ruta en storage/public o null (default)
 *
 * El tema se aplica en runtime vía App\Support\Theme::cssVars() inyectado en
 * el <head> (app.blade.php) y queda disponible en la SPA a través de
 * `siteSettings.theme` (ya compartido por HandleInertiaRequests).
 */
class AppearanceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Appearance/Index', [
            'theme'    => Theme::merged(json_decode(SiteSetting::getValue('theme') ?? 'null', true)),
            'defaults' => Theme::defaults(),
            'bgImage'  => SiteSetting::getValue('theme_bg_image'),
            'infoBgImage' => SiteSetting::getValue('home_info_bg_image'),
            'heroBgImage' => SiteSetting::getValue('home_hero_bg_image'),
            'heroBgVideo' => SiteSetting::getValue('home_hero_bg_video'),
            'brand' => [
                'logo_light'  => SiteSetting::getValue('logo_light'),
                'logo_dark'   => SiteSetting::getValue('logo_dark'),
                'favicon'     => SiteSetting::getValue('favicon'),
                'theme_color' => SiteSetting::getValue('seo_theme_color', '#3b82f6'),
                // PWA / manifest
                'pwa_icon'        => SiteSetting::getValue('pwa_icon'),
                'pwa_name'        => SiteSetting::getValue('pwa_name', ''),
                'pwa_short_name'  => SiteSetting::getValue('pwa_short_name', ''),
                'pwa_description' => SiteSetting::getValue('pwa_description', ''),
                'pwa_bg_color'    => SiteSetting::getValue('pwa_bg_color', '#0f172a'),
            ],
            'character' => [
                'enabled'  => SiteSetting::getValue('home_char_enabled', '1') !== '0',
                'mobile'   => SiteSetting::getValue('home_char_mobile', '0') === '1',
                'position' => SiteSetting::getValue('home_char_position', 'right'),
                'size'     => SiteSetting::getValue('home_char_size', ''),
                'frames'   => [
                    SiteSetting::getValue('home_char_frame1'),
                    SiteSetting::getValue('home_char_frame2'),
                    SiteSetting::getValue('home_char_frame3'),
                    SiteSetting::getValue('home_char_frame4'),
                ],
            ],
            // Iconos de las tarjetas de la home (info blocks + feature cards). El
            // texto/visibilidad es contenido (Settings); aquí se edita solo el icono.
            'cards' => [
                'info'    => json_decode(SiteSetting::getValue('home_info_blocks') ?? '[]', true) ?: [],
                'feature' => json_decode(SiteSetting::getValue('home_feature_cards') ?? '[]', true) ?: [],
            ],
        ]);
    }

    /**
     * Personaje animado de la home (HomeAlt /home2): 4 frames + posición/tamaño/
     * visibilidad. Guardado en ra_site_settings (home_char_*), compartido por
     * Inertia y consumido por HomeAlt.vue.
     */
    public function updateCharacter(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled'  => 'nullable|boolean',
            'mobile'   => 'nullable|boolean',
            'position' => 'nullable|in:left,center,right',
            'size'     => 'nullable|in:sm,md,lg',
            'frame1'   => 'nullable|image|mimes:png,webp|max:4096',
            'frame2'   => 'nullable|image|mimes:png,webp|max:4096',
            'frame3'   => 'nullable|image|mimes:png,webp|max:4096',
            'frame4'   => 'nullable|image|mimes:png,webp|max:4096',
            'remove_frame1' => 'nullable|boolean',
            'remove_frame2' => 'nullable|boolean',
            'remove_frame3' => 'nullable|boolean',
            'remove_frame4' => 'nullable|boolean',
        ]);

        SiteSetting::setMany([
            'home_char_enabled'  => $request->boolean('enabled') ? '1' : '0',
            'home_char_mobile'   => $request->boolean('mobile') ? '1' : '0',
            'home_char_position' => $request->input('position', 'right'),
            'home_char_size'     => $request->input('size', ''),
        ]);

        foreach ([1, 2, 3, 4] as $n) {
            if ($request->boolean("remove_frame{$n}")) {
                $this->deleteSetting("home_char_frame{$n}");
                SiteSetting::setValue("home_char_frame{$n}", null);
            } elseif ($request->hasFile("frame{$n}")) {
                $this->deleteSetting("home_char_frame{$n}");
                SiteSetting::setValue(
                    "home_char_frame{$n}",
                    $request->file("frame{$n}")->store('settings/character', 'public')
                );
            }
        }

        return back()->with('success', __('Character settings saved.'));
    }

    /**
     * Marca / identidad visual: logos (claro/oscuro), favicon y color de tema del
     * navegador. Movido desde Settings → General porque son assets de diseño.
     * Guardado en ra_site_settings (setMany invalida el cache compartido).
     */
    public function updateBrand(Request $request): RedirectResponse
    {
        $request->validate([
            'logo_light'  => 'nullable|image|max:2048',
            'logo_dark'   => 'nullable|image|max:2048',
            'favicon'     => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
            'theme_color' => 'nullable|string|max:20|regex:/^#[0-9a-fA-F]{3,8}$/',
            // PWA / manifest. El icono maestro genera todos los tamaños (GD), por eso
            // exige formato rasterizado (no SVG).
            'pwa_icon'        => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'pwa_name'        => 'nullable|string|max:60',
            'pwa_short_name'  => 'nullable|string|max:30',
            'pwa_description' => 'nullable|string|max:200',
            'pwa_bg_color'    => 'nullable|string|max:20|regex:/^#[0-9a-fA-F]{3,8}$/',
        ]);

        SiteSetting::setValue('seo_theme_color', $request->input('theme_color', '#3b82f6'));

        foreach (['logo_light', 'logo_dark', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                $this->deleteSetting($field);
                SiteSetting::setValue($field, $request->file($field)->store('settings', 'public'));
            }
        }

        // Textos / colores del manifest PWA
        SiteSetting::setMany([
            'pwa_name'        => $request->input('pwa_name', ''),
            'pwa_short_name'  => $request->input('pwa_short_name', ''),
            'pwa_description' => $request->input('pwa_description', ''),
            'pwa_bg_color'    => $request->input('pwa_bg_color', '#0f172a'),
        ]);

        // Icono maestro PWA → genera 192/512 (any + maskable) + apple-touch vía GD.
        if ($request->hasFile('pwa_icon')) {
            $this->deleteSetting('pwa_icon');
            $master = $request->file('pwa_icon')->store('settings/pwa', 'public');
            SiteSetting::setValue('pwa_icon', $master);
            $this->generatePwaIcons(Storage::disk('public')->path($master));
        }

        return back()->with('success', __('Appearance saved.'));
    }

    /**
     * Genera los tamaños del manifest PWA a partir del icono maestro con GD:
     * 192/512 (purpose any), 192/512 (maskable, contenido al 80% en zona segura) y
     * apple-touch 180. Cada variante se guarda en ra_site_settings (pwa_icon_*).
     */
    private function generatePwaIcons(string $absSource): void
    {
        if (!function_exists('imagecreatefromstring')) {
            return; // GD no disponible: se conservan los iconos estáticos de /icons.
        }

        $src = @imagecreatefromstring((string) @file_get_contents($absSource));
        if (!$src) {
            return;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);

        $render = function (int $size, float $scale) use ($src, $sw, $sh): string {
            $canvas = imagecreatetruecolor($size, $size);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefilledrectangle($canvas, 0, 0, $size, $size, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagealphablending($canvas, true);
            $target = (int) round($size * $scale);
            $offset = (int) round(($size - $target) / 2);
            imagecopyresampled($canvas, $src, $offset, $offset, 0, 0, $target, $target, $sw, $sh);
            ob_start();
            imagepng($canvas);
            $data = (string) ob_get_clean();
            imagedestroy($canvas);
            return $data;
        };

        $variants = [
            'pwa_icon_192'          => $render(192, 1.0),
            'pwa_icon_512'          => $render(512, 1.0),
            'pwa_icon_192_maskable' => $render(192, 0.8),
            'pwa_icon_512_maskable' => $render(512, 0.8),
            'pwa_apple_touch'       => $render(180, 1.0),
        ];
        imagedestroy($src);

        foreach ($variants as $key => $data) {
            $this->deleteSetting($key);
            $path = 'settings/pwa/' . $key . '_' . substr(md5($data), 0, 8) . '.png';
            Storage::disk('public')->put($path, $data);
            SiteSetting::setValue($key, $path);
        }
    }

    /**
     * Iconos de las tarjetas de la home (info blocks + feature cards). Escribe SOLO
     * los campos de diseño (icon_type/image/svg_code) dentro de los mismos JSON que
     * gestiona Settings, preservando el contenido (texto/visibilidad) — así ambos
     * editores comparten el JSON sin pisarse (read-merge).
     */
    public function updateHomeCards(Request $request): RedirectResponse
    {
        $request->validate([
            'blocks.*.icon_type' => 'nullable|in:icon,image,svg',
            'blocks.*.svg_code'  => 'nullable|string|max:20000',
            'blocks.*.image'     => 'nullable|image|mimes:png,webp,jpg,jpeg,svg|max:4096',
            'blocks.*.remove'    => 'nullable|boolean',
            'cards.*.icon_type'  => 'nullable|in:icon,image,svg',
            'cards.*.svg_code'   => 'nullable|string|max:20000',
            'cards.*.image'      => 'nullable|image|mimes:png,webp,jpg,jpeg,svg|max:4096',
            'cards.*.remove'     => 'nullable|boolean',
        ]);

        // ── Info blocks (5, ids fijos) ──
        $currentBlocks = json_decode(SiteSetting::getValue('home_info_blocks') ?? '[]', true);
        $ids    = ['rates', 'level', 'mode', 'episode', 'intl'];
        $blocks = [];
        foreach ($ids as $i => $id) {
            $existing = collect($currentBlocks)->firstWhere('id', $id) ?? [];
            [$iconType, $image, $svg] = $this->resolveCardIcon($request, "blocks.{$i}", $existing, 'settings/info');
            $blocks[] = [
                'id'        => $id,
                'show'      => $existing['show'] ?? true,   // contenido preservado
                'icon_type' => $iconType,
                'image'     => $image,
                'svg_code'  => $svg,
            ];
        }
        SiteSetting::setValue('home_info_blocks', json_encode($blocks));

        // ── Feature cards (6, por índice) ──
        $currentCards = json_decode(SiteSetting::getValue('home_feature_cards') ?? '[]', true);
        $cards = [];
        for ($i = 0; $i < 6; $i++) {
            $existing = $currentCards[$i] ?? [];
            [$iconType, $image, $svg] = $this->resolveCardIcon($request, "cards.{$i}", $existing, 'settings/features');
            $cards[] = [
                'title'     => $existing['title']   ?? null,   // contenido preservado
                'desc'      => $existing['desc']    ?? null,
                'enabled'   => $existing['enabled'] ?? true,
                'icon_type' => $iconType,
                'image'     => $image,
                'svg_code'  => $svg,
            ];
        }
        SiteSetting::setValue('home_feature_cards', json_encode($cards));

        Cache::forget('ra_site_settings');
        return back()->with('success', __('Appearance saved.'));
    }

    /**
     * Resuelve el icono de una tarjeta a partir del request: prioriza "volver al
     * default" → archivo subido → SVG pegado → preservar lo existente.
     * Devuelve [icon_type, image, svg_code].
     */
    private function resolveCardIcon(Request $request, string $prefix, array $existing, string $dir): array
    {
        $image = $existing['image']    ?? null;
        $svg   = $existing['svg_code'] ?? null;

        if ($request->boolean("{$prefix}.remove")) {
            if ($image) Storage::disk('public')->delete($image);
            return ['icon', null, null];
        }

        if ($request->hasFile("{$prefix}.image")) {
            if ($image) Storage::disk('public')->delete($image);
            return ['image', $request->file("{$prefix}.image")->store($dir, 'public'), null];
        }

        $newSvg = $request->input("{$prefix}.svg_code");
        if ($newSvg !== null && trim($newSvg) !== '' && $newSvg !== $svg) {
            if ($image) Storage::disk('public')->delete($image);
            return ['svg', null, $newSvg];
        }

        return [$existing['icon_type'] ?? 'icon', $image, $svg];
    }

    private function deleteSetting(string $key): void
    {
        $old = SiteSetting::getValue($key);
        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }

    public function update(Request $request): RedirectResponse
    {
        $hex = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $request->validate([
            // Secciones header / footer (color separado claro/oscuro)
            'header.light.bg'   => $hex, 'header.light.text' => $hex, 'header.light.link' => $hex,
            'header.dark.bg'    => $hex, 'header.dark.text'  => $hex, 'header.dark.link'  => $hex,
            'footer.light.bg'   => $hex, 'footer.light.text' => $hex, 'footer.light.link' => $hex,
            'footer.dark.bg'    => $hex, 'footer.dark.text'  => $hex, 'footer.dark.link'  => $hex,
            // Acentos de botones + globales claro/oscuro
            'buttons.blue'    => $hex,
            'buttons.gold'    => $hex,
            'buttons.purple'  => $hex,
            'buttons.navy'    => $hex,
            'buttons.success' => $hex,
            'buttons.danger'  => $hex,
            'light.bg'        => $hex,
            'light.text'      => $hex,
            'dark.bg'         => $hex,
            'dark.surface'    => $hex,
            'dark.text'       => $hex,
            // Estilo de la home: degradado de títulos, acento decorativo, paleta de tarjetas
            'home.title_gradient.from' => $hex,
            'home.title_gradient.mid'  => $hex,
            'home.title_gradient.to'   => $hex,
            'home.accent'     => $hex,
            'home.palette'    => 'array|size:6',
            'home.palette.*'  => $hex,
            'bg_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'remove_bg_image' => 'nullable|boolean',
            'info_bg_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:10240',
            'remove_info_bg_image' => 'nullable|boolean',
            // Media de fondo del hero de la home (imagen o video). Es identidad
            // visual → vive aquí en Appearance, no en Settings.
            'hero_bg_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'remove_hero_bg_image' => 'nullable|boolean',
            'hero_bg_video'        => 'nullable|mimes:mp4,webm|max:102400',
            'remove_hero_bg_video' => 'nullable|boolean',
        ]);

        // Normaliza sobre defaults para garantizar un tema completo y válido.
        $theme = Theme::merged([
            'header'  => $request->input('header'),
            'footer'  => $request->input('footer'),
            'buttons' => $request->input('buttons'),
            'light'   => $request->input('light'),
            'dark'    => $request->input('dark'),
            'home'    => $request->input('home'),
        ]);

        // Guardar dentro de ra_site_settings → setValue ya invalida su cache.
        SiteSetting::setValue('theme', json_encode($theme));

        // Imagen de fondo (mismo patrón que SiteSettingsController::updateHomeHero)
        if ($request->boolean('remove_bg_image')) {
            $this->deleteBgImage();
            SiteSetting::setValue('theme_bg_image', null);
        } elseif ($request->hasFile('bg_image')) {
            $this->deleteBgImage();
            SiteSetting::setValue(
                'theme_bg_image',
                $request->file('bg_image')->store('settings/theme', 'public')
            );
        }

        // Imagen de fondo de la sección "Server Info" de la home
        if ($request->boolean('remove_info_bg_image')) {
            $this->deleteSetting('home_info_bg_image');
            SiteSetting::setValue('home_info_bg_image', null);
        } elseif ($request->hasFile('info_bg_image')) {
            $this->deleteSetting('home_info_bg_image');
            SiteSetting::setValue(
                'home_info_bg_image',
                $request->file('info_bg_image')->store('settings/home', 'public')
            );
        }

        // Media de fondo del hero (imagen / video), movida desde Settings.
        if ($request->boolean('remove_hero_bg_image')) {
            $this->deleteSetting('home_hero_bg_image');
            SiteSetting::setValue('home_hero_bg_image', null);
        } elseif ($request->hasFile('hero_bg_image')) {
            $this->deleteSetting('home_hero_bg_image');
            SiteSetting::setValue(
                'home_hero_bg_image',
                $request->file('hero_bg_image')->store('settings/hero', 'public')
            );
        }

        if ($request->boolean('remove_hero_bg_video')) {
            $this->deleteSetting('home_hero_bg_video');
            SiteSetting::setValue('home_hero_bg_video', null);
        } elseif ($request->hasFile('hero_bg_video')) {
            $this->deleteSetting('home_hero_bg_video');
            SiteSetting::setValue(
                'home_hero_bg_video',
                $request->file('hero_bg_video')->store('settings/hero', 'public')
            );
        }

        return back()->with('success', __('Appearance saved.'));
    }

    public function reset(): RedirectResponse
    {
        $this->deleteBgImage();

        // Borrar los registros → el sitio cae a los fallbacks del config (idéntico a una instalación limpia).
        SiteSetting::where('key', 'theme')->delete();
        SiteSetting::where('key', 'theme_bg_image')->delete();
        Cache::forget('ra_site_settings');

        return back()->with('success', __('Appearance reset to defaults.'));
    }

    public function removeImage(): RedirectResponse
    {
        $this->deleteBgImage();
        SiteSetting::setValue('theme_bg_image', null);

        return back()->with('success', __('Appearance saved.'));
    }

    private function deleteBgImage(): void
    {
        $old = SiteSetting::getValue('theme_bg_image');
        if ($old) {
            Storage::disk('public')->delete($old);
        }
    }
}
