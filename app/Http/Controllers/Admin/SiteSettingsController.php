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
        $request->validate([
            'site_name'          => 'required|string|max:100',
            'logo_light'         => 'nullable|image|max:2048',
            'logo_dark'          => 'nullable|image|max:2048',
            'favicon'            => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
            'seo_theme_color'    => 'nullable|string|max:20|regex:/^#[0-9a-fA-F]{3,8}$/',
            'discord_server_id'  => 'nullable|string|max:100',
            'discord_invite_url' => 'nullable|url|max:500',
        ]);

        $data = [
            'site_name'          => $request->site_name,
            'seo_theme_color'    => $request->seo_theme_color ?? '#3b82f6',
            'discord_server_id'  => $request->discord_server_id ?? '',
            'discord_invite_url' => $request->discord_invite_url ?? '',
        ];

        foreach (['logo_light', 'logo_dark', 'favicon'] as $field) {
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
        $request->validate([
            'home_hero_subtitle'     => 'nullable|string|max:300',
            'home_learn_more_url'    => 'nullable|url|max:500',
            'home_hero_bg_image'     => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'home_hero_bg_video'     => 'nullable|mimes:mp4,webm|max:102400',
            'remove_hero_bg_video'   => 'nullable|boolean',
            'remove_hero_bg_image'   => 'nullable|boolean',
        ]);

        $data = [
            'home_hero_subtitle'  => $request->home_hero_subtitle  ?? '',
            'home_learn_more_url' => $request->home_learn_more_url ?? '',
        ];

        // Eliminar video si se solicitó
        if ($request->boolean('remove_hero_bg_video')) {
            $old = SiteSetting::getValue('home_hero_bg_video');
            if ($old) Storage::disk('public')->delete($old);
            $data['home_hero_bg_video'] = null;
        }

        // Eliminar imagen si se solicitó
        if ($request->boolean('remove_hero_bg_image')) {
            $old = SiteSetting::getValue('home_hero_bg_image');
            if ($old) Storage::disk('public')->delete($old);
            $data['home_hero_bg_image'] = null;
        }

        // Subir archivos nuevos
        foreach (['home_hero_bg_image', 'home_hero_bg_video'] as $field) {
            if ($request->hasFile($field)) {
                $old = SiteSetting::getValue($field);
                if ($old) Storage::disk('public')->delete($old);
                $data[$field] = $request->file($field)->store('settings/hero', 'public');
            }
        }

        SiteSetting::setMany($data);

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
