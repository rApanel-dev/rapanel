<?php

namespace App\Http\Middleware;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Middleware;

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

            'serverName'      => config('services.ra.server_name', 'rApanel'),
            'discordServerId' => config('services.discord.server_id'),

            'latestNews' => Cache::remember('home_latest_news', 120, function () {
                return News::published()
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['id', 'title', 'slug', 'type', 'featured_image', 'body', 'created_at', 'is_pinned'])
                    ->map(fn ($n) => [
                        'id'             => $n->id,
                        'slug'           => $n->slug,
                        'title'          => $n->title,
                        'type'           => $n->type,
                        'type_label'     => News::typeLabel($n->type),
                        'featured_image' => $n->featured_image ? asset('storage/' . $n->featured_image) : null,
                        'excerpt'        => $n->excerpt,
                        'created_at'     => $n->created_at?->diffForHumans(),
                        'is_pinned'      => (bool) $n->is_pinned,
                    ])
                    ->toArray();
            }),

            'discordStatus' => Cache::remember('discord_widget_status', 300, function () {
                return $this->fetchDiscordStatus();
            }),

            'flash' => fn () => array_filter([
                'success'         => $request->session()->get('success'),
                'error'           => $request->session()->get('error'),
                'maps_imported'   => $request->session()->get('maps_imported'),
                'maps_expected'   => $request->session()->get('maps_expected'),
                'spawns_imported' => $request->session()->get('spawns_imported'),
                'spawns_failed'   => $request->session()->get('spawns_failed'),
                'maps_affected'   => $request->session()->get('maps_affected'),
                'maps_detail'     => $request->session()->get('maps_detail'),
                'skipped_lines'   => $request->session()->get('skipped_lines'),
            ], fn ($v) => $v !== null),
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

    private function fetchDiscordStatus(): array
    {
        $token    = config('services.discord.bot_token');
        $serverId = config('services.discord.server_id');
        $invite   = config('services.discord.invite_url', '#');

        $base = ['invite_url' => $invite, 'members' => null, 'online' => null, 'name' => null];

        if (! $token || ! $serverId) {
            return $base;
        }

        try {
            $response = Http::withToken($token, 'Bot')
                ->timeout(3)
                ->get("https://discord.com/api/v10/guilds/{$serverId}?with_counts=true");

            if ($response->ok()) {
                $data = $response->json();
                return array_merge($base, [
                    'name'    => $data['name'] ?? null,
                    'members' => $data['approximate_member_count'] ?? null,
                    'online'  => $data['approximate_presence_count'] ?? null,
                    'icon'    => isset($data['icon'])
                        ? "https://cdn.discordapp.com/icons/{$serverId}/{$data['icon']}.png"
                        : null,
                ]);
            }
        } catch (\Exception) {}

        return $base;
    }

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
