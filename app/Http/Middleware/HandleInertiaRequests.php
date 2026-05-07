<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Agregamos esto para enviar las traducciones a Vue
            'translations' => function () {
                $locale = \App::getLocale();
                $file = base_path("lang/{$locale}.json");
                
                if (file_exists($file)) {
                    return json_decode(file_get_contents($file), true);
                }

                return [];
            },
            // Mantenemos el locale por si lo necesitas para las banderas
            'locale' => \App::getLocale(),
        ];
    }
}
