<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Cache;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            
            // Estado del servidor con Cache de 60 segundos
            'serverStatus' => Cache::remember('ra_server_status', 60, function () {
                return [
                    'online' => $this->checkRAthenaStatus(),
                    'players' => $this->getOnlinePlayersCount(),
                ];
            }),

            'translations' => function () {
                $locale = \App::getLocale();
                $file = base_path("lang/{$locale}.json");
                return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
            },

            'locale' => \App::getLocale(),

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Valida la conexión a los puertos de rAthena usando el .env
     */
    private function checkRAthenaStatus(): bool
    {
        // Leemos las variables que me pasaste de tu .env
        $ip   = env('RA_LOGIN_IP', '127.0.0.1');
        $port = env('RA_LOGIN_PORT', 6900);

        // Intentamos conexión rápida (timeout 1 segundo)
        $connection = @fsockopen($ip, $port, $errno, $errstr, 1);

        if ($connection) {
            fclose($connection);
            return true;
        }

        return false;
    }

    /**
     * Obtiene el conteo de jugadores desde la DB
     * (De momento devuelve 0 hasta que configuremos tu conexión a la DB de rAthena)
     */

    private function getOnlinePlayersCount(): int
    {
        try {
            // Usamos la conexión 'mysql_main' que definiste en database.php
            return \Illuminate\Support\Facades\DB::connection('mysql_main')
                ->table('char')
                ->where('online', '>', 0)
                ->count();
        } catch (\Exception $e) {
            // Si hay error (ej: DB no configurada aún), devolvemos 0
            return 0;
        }
    }
}
