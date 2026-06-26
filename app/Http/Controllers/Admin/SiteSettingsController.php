<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    private function clearCache(): void
    {
        Cache::forget('ra_site_settings');
    }

    public function index(): Response
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return Inertia::render('Admin/SiteSettings/Index', [
            'settings' => $settings,
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        // Logos, favicon y color de tema se gestionan en Appearance → Brand.
        $request->validate([
            'site_name'          => 'required|string|max:100',
            'discord_server_id'  => 'nullable|string|max:100',
            'discord_invite_url' => 'nullable|url|max:500',
        ]);

        SiteSetting::setMany([
            'site_name'          => $request->site_name,
            'discord_server_id'  => $request->discord_server_id ?? '',
            'discord_invite_url' => $request->discord_invite_url ?? '',
        ]);

        $this->clearCache();
        return back()->with('success', __('General settings saved.'));
    }

    public function updateHome(Request $request): RedirectResponse
    {
        $request->validate([
            'home_base_rate'      => 'nullable|string|max:50',
            'home_job_rate'       => 'nullable|string|max:50',
            'home_drop_rate'      => 'nullable|string|max:50',
            'home_max_base_level' => 'nullable|string|max:20',
            'home_max_job_level'  => 'nullable|string|max:20',
            'home_game_mode'      => 'nullable|string|max:50',
            'home_episode'        => 'nullable|string|max:200',
        ]);

        SiteSetting::setMany([
            'home_base_rate'      => $request->home_base_rate      ?? '100',
            'home_job_rate'       => $request->home_job_rate       ?? '100',
            'home_drop_rate'      => $request->home_drop_rate      ?? '100',
            'home_max_base_level' => $request->home_max_base_level ?? '99',
            'home_max_job_level'  => $request->home_max_job_level  ?? '70',
            'home_game_mode'      => $request->home_game_mode      ?? '',
            'home_episode'        => $request->home_episode        ?? '',
        ]);

        $this->clearCache();
        return back()->with('success', __('Home settings saved.'));
    }

    public function updateHomeHero(Request $request): RedirectResponse
    {
        // Solo CONTENIDO del hero (subtítulo + URL "Learn More"). La media de fondo
        // (imagen/video) se gestiona ahora en Appearance (AppearanceController::update).
        $request->validate([
            'home_hero_subtitle'  => 'nullable|string|max:300',
            'home_learn_more_url' => 'nullable|url|max:500',
        ]);

        SiteSetting::setMany([
            'home_hero_subtitle'  => $request->home_hero_subtitle  ?? '',
            'home_learn_more_url' => $request->home_learn_more_url ?? '',
        ]);

        $this->clearCache();
        return back()->with('success', __('Home settings saved.'));
    }

    public function updateHomeInfoBlocks(Request $request): RedirectResponse
    {
        $request->validate([
            'home_base_rate'      => 'nullable|string|max:50',
            'home_job_rate'       => 'nullable|string|max:50',
            'home_drop_rate'      => 'nullable|string|max:50',
            'home_max_base_level' => 'nullable|string|max:20',
            'home_max_job_level'  => 'nullable|string|max:20',
            'home_game_mode'      => 'nullable|string|max:50',
            'home_episode'        => 'nullable|string|max:200',
            'home_intl_text'      => 'nullable|string|max:100',
        ]);

        // Guardar valores de texto de los bloques
        SiteSetting::setMany([
            'home_base_rate'      => $request->home_base_rate      ?? '100',
            'home_job_rate'       => $request->home_job_rate       ?? '100',
            'home_drop_rate'      => $request->home_drop_rate      ?? '100',
            'home_max_base_level' => $request->home_max_base_level ?? '99',
            'home_max_job_level'  => $request->home_max_job_level  ?? '70',
            'home_game_mode'      => $request->home_game_mode      ?? '',
            'home_episode'        => $request->home_episode        ?? '',
            'home_intl_text'      => $request->home_intl_text      ?? 'EN · ES · PT · FR',
        ]);

        // Guardar config de bloques (toggle + icono/imagen)
        $current = json_decode(SiteSetting::getValue('home_info_blocks') ?? '[]', true);
        $ids     = ['rates', 'level', 'mode', 'episode', 'intl'];
        $blocks  = [];

        foreach ($ids as $i => $id) {
            $existing  = collect($current)->firstWhere('id', $id) ?? [];
            $iconType  = $request->input("blocks.{$i}.icon_type", $existing['icon_type'] ?? 'icon');
            $show      = $request->boolean("blocks.{$i}.show", $existing['show'] ?? true);
            $image     = $existing['image'] ?? null;

            // Eliminar imagen custom si se pidió volver al default
            if ($request->boolean("blocks.{$i}.remove_image") || $iconType === 'icon') {
                if ($image) Storage::disk('public')->delete($image);
                $image = null;
            }

            if ($request->hasFile("blocks.{$i}.image")) {
                if ($image) Storage::disk('public')->delete($image);
                $image    = $request->file("blocks.{$i}.image")->store('settings/info', 'public');
                $iconType = 'image';
            }

            $svgCode = $request->input("blocks.{$i}.svg_code", $existing['svg_code'] ?? null);
            // Si se sube archivo nuevo, descarta el svg_code
            if ($request->hasFile("blocks.{$i}.image")) $svgCode = null;
            // Si se pega svg_code nuevo, descarta la imagen
            if ($svgCode && $request->input("blocks.{$i}.svg_code") !== ($existing['svg_code'] ?? null)) {
                if ($image) Storage::disk('public')->delete($image);
                $image = null;
            }

            // El color de acento ya no se guarda por bloque: lo define la paleta
            // editable en Appearance → Home → Card palette (consumida por HomeAlt).
            $blocks[] = ['id' => $id, 'show' => $show, 'icon_type' => $iconType, 'image' => $image, 'svg_code' => $svgCode];
        }

        SiteSetting::setValue('home_info_blocks', json_encode($blocks));

        $this->clearCache();
        return back()->with('success', __('Home settings saved.'));
    }

    public function updateHomeHighlightCards(Request $request): RedirectResponse
    {
        $current = json_decode(SiteSetting::getValue('home_highlight_cards') ?? '[]', true);
        $count   = 4;
        $cards   = [];

        for ($i = 0; $i < $count; $i++) {
            $existing = $current[$i] ?? [];
            $image    = $existing['image'] ?? null;

            if ($request->hasFile("cards.{$i}.image")) {
                if ($image) Storage::disk('public')->delete($image);
                $image = $request->file("cards.{$i}.image")->store('settings/cards', 'public');
            }

            $cards[] = [
                'show'  => $request->boolean("cards.{$i}.show", $existing['show'] ?? true),
                'image' => $image,
                'title' => $request->input("cards.{$i}.title", $existing['title'] ?? ''),
                'desc'  => $request->input("cards.{$i}.desc",  $existing['desc']  ?? ''),
                'url'   => $request->input("cards.{$i}.url",   $existing['url']   ?? '#'),
            ];
        }

        SiteSetting::setValue('home_highlight_cards', json_encode($cards));

        $this->clearCache();
        return back()->with('success', __('Home settings saved.'));
    }

    public function updateHomeSections(Request $request): RedirectResponse
    {
        SiteSetting::setMany([
            'home_show_stats'    => $request->boolean('home_show_stats')    ? '1' : '0',
            'home_show_info'     => $request->boolean('home_show_info')     ? '1' : '0',
            'home_show_news'     => $request->boolean('home_show_news')     ? '1' : '0',
            'home_show_features' => $request->boolean('home_show_features') ? '1' : '0',
            'home_show_cta'      => $request->boolean('home_show_cta')      ? '1' : '0',
        ]);

        $this->clearCache();
        return back()->with('success', __('Home settings saved.'));
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $request->validate([
            'seo_title'                => 'required|string|max:200',
            'seo_description'          => 'nullable|string|max:500',
            'seo_og_image'             => 'nullable|image|max:2048',
            'google_site_verification' => 'nullable|string|max:200',
        ]);

        $data = [
            'seo_title'                => $request->seo_title,
            'seo_description'          => $request->seo_description ?? '',
            'google_site_verification' => $request->google_site_verification ?? '',
        ];

        foreach (['seo_og_image'] as $field) {
            if ($request->hasFile($field)) {
                $old = SiteSetting::getValue($field);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $data[$field] = $request->file($field)->store('settings', 'public');
            }
        }

        SiteSetting::setMany($data);

        $this->clearCache();
        return back()->with('success', __('SEO settings saved.'));
    }

    public function updateSocialNetworks(Request $request): RedirectResponse
    {
        $networks = ['discord', 'facebook', 'instagram', 'twitter', 'youtube', 'tiktok'];

        $rules = [];
        foreach ($networks as $id) {
            $rules["networks.{$id}.enabled"] = 'nullable|boolean';
            $rules["networks.{$id}.url"]     = 'nullable|url|max:500';
        }
        $request->validate($rules);

        $data = [];
        foreach ($networks as $id) {
            $data[] = [
                'id'      => $id,
                'label'   => match ($id) {
                    'discord'   => 'Discord',
                    'facebook'  => 'Facebook',
                    'instagram' => 'Instagram',
                    'twitter'   => 'Twitter / X',
                    'youtube'   => 'YouTube',
                    'tiktok'    => 'TikTok',
                },
                'enabled' => $request->boolean("networks.{$id}.enabled"),
                'url'     => $request->input("networks.{$id}.url", ''),
            ];
        }

        SiteSetting::setValue('social_networks', json_encode($data));

        $this->clearCache();
        return back()->with('success', __('Social networks saved.'));
    }

    public function updateHomeFeatures(Request $request): RedirectResponse
    {
        $request->validate([
            'features_title'    => 'nullable|string|max:200',
            'features_subtitle' => 'nullable|string|max:200',
        ]);

        SiteSetting::setMany([
            'home_features_title'    => $request->features_title    ?? '',
            'home_features_subtitle' => $request->features_subtitle ?? '',
        ]);

        $defaults = [
            ['color' => '#4A90E2', 'title' => 'Dashboard',    'desc' => 'Create and manage game accounts, view characters, reset position — all from your browser.'],
            ['color' => '#F1C40F', 'title' => 'Item DB',      'desc' => 'Full searchable item database imported from rAthena YAML + itemInfo.lua with drop sources and card slots.'],
            ['color' => '#2ECC71', 'title' => 'Wiki',         'desc' => 'Docs-style public knowledge base organized in sections and articles with a rich-text editor.'],
            ['color' => '#a855f7', 'title' => 'MvP Cards',   'desc' => 'Live MvP card browser with holder counts, item properties and illustrated card portraits.'],
            ['color' => '#4A90E2', 'title' => 'Live Console', 'desc' => 'Real-time stdout/stderr for each server process via WebSocket. Start, stop, restart with one click.'],
            ['color' => '#F1C40F', 'title' => 'Who Sell',     'desc' => 'Search the live vending market by item name or ID and find sellers in real time.'],
        ];

        $current = json_decode(SiteSetting::getValue('home_feature_cards') ?? '[]', true);
        $cards   = [];

        for ($i = 0; $i < 6; $i++) {
            $existing  = $current[$i] ?? [];
            $iconType  = $request->input("cards.{$i}.icon_type", $existing['icon_type'] ?? 'icon');
            $enabled   = $request->boolean("cards.{$i}.enabled", $existing['enabled'] ?? true);
            $image     = $existing['image'] ?? null;

            // Volver al ícono por defecto borra la imagen guardada
            if ($iconType === 'icon') {
                if ($image) Storage::disk('public')->delete($image);
                $image = null;
            }

            // Nueva imagen sube el archivo
            if ($request->hasFile("cards.{$i}.image")) {
                if ($image) Storage::disk('public')->delete($image);
                $image    = $request->file("cards.{$i}.image")->store('settings/features', 'public');
                $iconType = 'image';
            }

            $svgCode = $request->input("cards.{$i}.svg_code", $existing['svg_code'] ?? null) ?: null;
            // Si se sube imagen, descarta svg_code
            if ($request->hasFile("cards.{$i}.image")) $svgCode = null;
            // Si se pega svg_code nuevo, descarta la imagen
            if ($svgCode && $svgCode !== ($existing['svg_code'] ?? null)) {
                if ($image) Storage::disk('public')->delete($image);
                $image = null;
            }

            // El color de acento ya no se guarda por tarjeta: lo define la paleta
            // editable en Appearance → Home → Card palette (consumida por HomeAlt).
            $cards[] = [
                'title'     => $request->input("cards.{$i}.title",  $existing['title']  ?? $defaults[$i]['title']),
                'desc'      => $request->input("cards.{$i}.desc",   $existing['desc']   ?? $defaults[$i]['desc']),
                'svg_code'  => $svgCode,
                'image'     => $image,
                'icon_type' => $iconType,
                'enabled'   => $enabled,
            ];
        }

        SiteSetting::setValue('home_feature_cards', json_encode($cards));

        $this->clearCache();
        return back()->with('success', __('Panel Features settings saved.'));
    }

    public function updateHomeCta(Request $request): RedirectResponse
    {
        $request->validate([
            'cta_line1'   => 'nullable|string|max:100',
            'cta_line2'   => 'nullable|string|max:100',
            'cta_subtitle'=> 'nullable|string|max:300',
            'cta_btn_text'=> 'nullable|string|max:60',
            'cta_btn_url' => 'nullable|string|max:500',
        ]);

        SiteSetting::setMany([
            'home_cta_line1'    => $request->cta_line1    ?? '',
            'home_cta_line2'    => $request->cta_line2    ?? '',
            'home_cta_subtitle' => $request->cta_subtitle ?? '',
            'home_cta_btn_text' => $request->cta_btn_text ?? '',
            'home_cta_btn_url'  => $request->cta_btn_url  ?? '',
        ]);

        $this->clearCache();
        return back()->with('success', __('Call to Action settings saved.'));
    }

    public function dangerClearLogs(Request $request): RedirectResponse
    {
        $count = ActionLog::count();
        ActionLog::truncate();

        return back()->with('success', __(':count action log entries deleted.', ['count' => $count]));
    }

    public function dangerClearCache(Request $request): RedirectResponse
    {
        Artisan::call('cache:clear');

        return back()->with('success', __('Site cache cleared successfully.'));
    }

    public function dangerClearSessions(Request $request): RedirectResponse
    {
        $count = DB::table('sessions')->count();
        DB::table('sessions')->truncate();

        return back()->with('success', __(':count sessions cleared.', ['count' => $count]));
    }
}
