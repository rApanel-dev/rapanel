<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use App\Listeners\UpdateUserLoginDetails;
use App\Listeners\WarmGeoCache;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        View::composer('app', function ($view) {
            try {
                $settings = Cache::remember('ra_site_settings', 300, fn () =>
                    SiteSetting::pluck('value', 'key')->toArray()
                );
            } catch (\Exception) {
                $settings = [];
            }
            $view->with('siteSettings', $settings);
        });

        Event::listen(Login::class, UpdateUserLoginDetails::class);
        Event::listen(Login::class, WarmGeoCache::class);
    }
}
