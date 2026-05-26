<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
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
            'discord_server_id'  => 'nullable|string|max:100',
            'discord_invite_url' => 'nullable|url|max:500',
        ]);

        $data = [
            'site_name'          => $request->site_name,
            'discord_server_id'  => $request->discord_server_id ?? '',
            'discord_invite_url' => $request->discord_invite_url ?? '',
        ];

        foreach (['logo_light', 'logo_dark'] as $field) {
            if ($request->hasFile($field)) {
                $old = SiteSetting::getValue($field);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $data[$field] = $request->file($field)->store('settings', 'public');
            }
        }

        SiteSetting::setMany($data);

        return back()->with('success', __('General settings saved.'));
    }

    public function updateHome(Request $request): RedirectResponse
    {
        $request->validate([
            'home_base_rate'      => 'required|string|max:50',
            'home_job_rate'       => 'required|string|max:50',
            'home_max_base_level' => 'required|string|max:20',
            'home_max_job_level'  => 'required|string|max:20',
            'home_episode'        => 'nullable|string|max:200',
            'home_about_text'     => 'nullable|string',
            'home_community_text' => 'nullable|string',
        ]);

        SiteSetting::setMany([
            'home_base_rate'      => $request->home_base_rate,
            'home_job_rate'       => $request->home_job_rate,
            'home_max_base_level' => $request->home_max_base_level,
            'home_max_job_level'  => $request->home_max_job_level,
            'home_episode'        => $request->home_episode ?? '',
            'home_about_text'     => $request->home_about_text ?? '',
            'home_community_text' => $request->home_community_text ?? '',
        ]);

        return back()->with('success', __('Home settings saved.'));
    }

    public function updateSeo(Request $request): RedirectResponse
    {
        $request->validate([
            'seo_title'       => 'required|string|max:200',
            'seo_description' => 'nullable|string|max:500',
            'seo_theme_color' => 'nullable|string|max:20|regex:/^#[0-9a-fA-F]{3,8}$/',
            'favicon'         => 'nullable|image|mimes:png,jpg,jpeg,ico,svg|max:512',
            'seo_og_image'    => 'nullable|image|max:2048',
        ]);

        $data = [
            'seo_title'       => $request->seo_title,
            'seo_description' => $request->seo_description ?? '',
            'seo_theme_color' => $request->seo_theme_color ?? '#3b82f6',
        ];

        foreach (['favicon', 'seo_og_image'] as $field) {
            if ($request->hasFile($field)) {
                $old = SiteSetting::getValue($field);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $data[$field] = $request->file($field)->store('settings', 'public');
            }
        }

        SiteSetting::setMany($data);

        return back()->with('success', __('SEO settings saved.'));
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
