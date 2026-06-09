<?php

namespace App\Http\Middleware;

use App\Models\News;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
                $ra = config('services.ra');
                $login = $this->checkPort($ra['login_ip'], (int) $ra['login_port']);
                $char  = $this->checkPort($ra['char_ip'],  (int) $ra['char_port']);
                $map   = $this->checkPort($ra['map_ip'],   (int) $ra['map_port']);
                $web   = $this->checkPort($ra['web_ip'],   (int) $ra['web_port']);
                $ws    = $this->checkPort($ra['login_ip'], (int) $ra['ws_port']);
                $peak = DB::table('onlinepeak')
                    ->orderByDesc('users')
                    ->value('users') ?? 0;

                return [
                    'online'   => $login,
                    'players'  => $this->getOnlinePlayersCount(),
                    'vendings' => (int) DB::connection('mysql_main')->table('vendings')->count(),
                    'peak'     => (int) $peak,
                    'login'    => $login,
                    'char'     => $char,
                    'map'      => $map,
                    'web'      => $web,
                    'ws'       => $ws,
                ];
            }),

            'translations' => function () {
                $locale = \App::getLocale();
                $file = base_path("lang/{$locale}.json");
                return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
            },

            'locale' => \App::getLocale(),

            'siteSettings' => Cache::remember('ra_site_settings', 300, function () {
                return SiteSetting::pluck('value', 'key')->toArray();
            }),

            'serverName'      => config('services.ra.server_name', 'rApanel'),
            'baseExpRate'     => config('services.ra.base_exp_rate', 1),
            'jobExpRate'      => config('services.ra.job_exp_rate', 1),
            'mvpExpRate'      => config('services.ra.mvp_exp_rate', 100),
            'roBrowserUrl'    => config('services.ra.robrowser_url', ''),
            'discordServerId' => config('services.discord.server_id'),
            'twoFactorEnabled'     => config('services.ra.2fa_enabled', false),
            'twoFactorForceAdmins' => config('services.ra.2fa_force_admins', false),
            'inactivityTimeout'    => config('services.ra.inactivity_timeout', 30),

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
                        'type_color'     => News::typeColor($n->type),
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

    private function checkPort(string $ip, int $port): bool
    {
        $ip = ($ip === '0.0.0.0') ? '127.0.0.1' : $ip;
        $conn = @fsockopen($ip, $port, $errno, $errstr, 0.5);
        if ($conn) { fclose($conn); return true; }
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
