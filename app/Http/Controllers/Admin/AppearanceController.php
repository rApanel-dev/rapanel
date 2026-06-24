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
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $hex = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $request->validate([
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
            'bg_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'remove_bg_image' => 'nullable|boolean',
        ]);

        // Normaliza sobre defaults para garantizar un tema completo y válido.
        $theme = Theme::merged([
            'buttons' => $request->input('buttons'),
            'light'   => $request->input('light'),
            'dark'    => $request->input('dark'),
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
