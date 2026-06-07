<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class HealthCheckService
{
    public function run(): array
    {
        return array_merge(
            $this->checkDatabases(),
            $this->checkConnectivity(),
            $this->checkServices(),
            $this->checkOptional(),
        );
    }

    // -------------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------------

    private function checkDatabases(): array
    {
        $driver = env('DB_CONNECTION', 'mysql');

        return [
            $this->tryCheck('db_panel', 'BD Panel', 'database', function () {
                DB::connection('mysql')->getPdo();
                $count = DB::table('users')->count();
                return "Conectado. {$count} usuarios registrados.";
            }),

            $this->tryCheck('db_rathena', 'BD rAthena (mysql_main)', 'database', function () {
                DB::connection('mysql_main')->getPdo();
                $count = DB::connection('mysql_main')->table('login')->count();
                return "Conectado. {$count} cuentas de juego.";
            }),

            $this->tryCheck('db_log', 'BD Log (mysql_log)', 'database', function () {
                DB::connection('mysql_log')->getPdo();
                return 'Conectado.';
            }),

            $this->tryCheck('db_web', 'BD Web (mysql_web)', 'database', function () {
                DB::connection('mysql_web')->getPdo();
                return 'Conectado.';
            }),

            $this->checkMigrations(),
        ];
    }

    private function checkMigrations(): array
    {
        try {
            Artisan::call('migrate:status', ['--no-interaction' => true]);
            $output = Artisan::output();

            if (str_contains($output, 'Pending')) {
                return $this->result('migrations', 'Migraciones pendientes', 'database', 'warning', 'Hay migraciones sin ejecutar.');
            }

            return $this->result('migrations', 'Migraciones', 'database', 'ok', 'Sin cambios pendientes.');
        } catch (Throwable $e) {
            return $this->result('migrations', 'Migraciones', 'database', 'error', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Connectivity
    // -------------------------------------------------------------------------

    private function checkConnectivity(): array
    {
        $checks = [
            $this->checkTcp('tcp_login', 'Login Server', config('services.ra.login_ip'), config('services.ra.login_port')),
            $this->checkTcp('tcp_char',  'Char Server',  config('services.ra.char_ip'),  config('services.ra.char_port')),
            $this->checkTcp('tcp_map',   'Map Server',   config('services.ra.map_ip'),   config('services.ra.map_port')),
        ];

        $roUrl = config('services.ra.robrowser_url');
        if ($roUrl) {
            $checks[] = $this->tryCheck('robrowser', 'RoBrowser', 'connectivity', function () use ($roUrl) {
                $response = Http::timeout(5)->get($roUrl);
                if ($response->successful()) {
                    return "Respondiendo correctamente — {$roUrl}";
                }
                throw new \RuntimeException("HTTP {$response->status()}");
            });
        } else {
            $checks[] = $this->result('robrowser', 'RoBrowser', 'connectivity', 'skipped', 'RA_ROBROWSER_URL no configurado.');
        }

        $wsSecret = config('services.ra.ws_secret');
        $wsUrl    = config('services.ra.ws_internal_url');

        if ($wsSecret && $wsUrl) {
            $checks[] = $this->tryCheck('ws_server', 'ws-server HTTP API', 'connectivity', function () use ($wsUrl, $wsSecret) {
                $response = Http::withToken($wsSecret)->timeout(3)->get(rtrim($wsUrl, '/') . '/api/status');
                if ($response->successful()) {
                    return 'Respondiendo correctamente.';
                }
                throw new \RuntimeException("HTTP {$response->status()}");
            });
        } else {
            $checks[] = $this->result('ws_server', 'ws-server HTTP API', 'connectivity', 'skipped', 'RA_WS_SECRET o RA_WS_INTERNAL_URL no configurados.');
        }

        return $checks;
    }

    private function checkTcp(string $id, string $label, ?string $ip, ?int $port): array
    {
        if (!$ip || !$port) {
            return $this->result($id, $label, 'connectivity', 'skipped', 'IP o puerto no configurados.');
        }

        $displayIp = ($ip === '0.0.0.0') ? '127.0.0.1' : $ip;

        try {
            $sock = @fsockopen($displayIp, $port, $errno, $errstr, 3);
            if ($sock) {
                fclose($sock);
                return $this->result($id, "{$label} ({$port})", 'connectivity', 'ok', "Online — {$displayIp}:{$port}");
            }
            return $this->result($id, "{$label} ({$port})", 'connectivity', 'error', "Sin respuesta — {$errstr}");
        } catch (Throwable $e) {
            return $this->result($id, "{$label} ({$port})", 'connectivity', 'error', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Services
    // -------------------------------------------------------------------------

    private function checkServices(): array
    {
        return [
            $this->checkAppKey(),
            $this->checkMail(),
            $this->checkCache(),
            $this->checkStorage(),
            $this->checkQueue(),
            $this->checkTwoFactor(),
        ];
    }

    private function checkAppKey(): array
    {
        $key = config('app.key');
        if ($key) {
            return $this->result('app_key', 'APP_KEY', 'services', 'ok', 'Configurado.');
        }
        return $this->result('app_key', 'APP_KEY', 'services', 'error', 'No configurado — la aplicación no puede cifrar datos.');
    }

    private function checkMail(): array
    {
        $mailer = config('mail.mailer');
        if ($mailer === 'log') {
            return $this->result('mail', 'Correo electrónico', 'services', 'warning', "Driver '{$mailer}' — los correos no se envían, solo se escriben en el log.");
        }
        $host = config('mail.mailers.smtp.host');
        if (!$host && $mailer === 'smtp') {
            return $this->result('mail', 'Correo electrónico', 'services', 'warning', "Driver SMTP pero MAIL_HOST no configurado.");
        }
        return $this->result('mail', 'Correo electrónico', 'services', 'ok', "Driver: {$mailer}.");
    }

    private function checkCache(): array
    {
        return $this->tryCheck('cache', 'Cache', 'services', function () {
            $key = '_health_check_' . time();
            Cache::put($key, 'ok', 5);
            $val = Cache::get($key);
            Cache::forget($key);
            if ($val !== 'ok') {
                throw new \RuntimeException('El valor leído no coincide con el escrito.');
            }
            return 'Lectura y escritura funcionan correctamente.';
        });
    }

    private function checkStorage(): array
    {
        $paths = [
            storage_path('app')  => 'storage/app',
            storage_path('logs') => 'storage/logs',
        ];

        $failed = [];
        foreach ($paths as $path => $label) {
            if (!is_writable($path)) {
                $failed[] = $label;
            }
        }

        if ($failed) {
            return $this->result('storage', 'Storage', 'services', 'error', 'Sin permisos de escritura en: ' . implode(', ', $failed));
        }

        return $this->result('storage', 'Storage', 'services', 'ok', 'Directorios escribibles.');
    }

    private function checkQueue(): array
    {
        return $this->tryCheck('queue', 'Queue (database)', 'services', function () {
            DB::table('jobs')->count();
            return 'Tabla de cola accesible.';
        });
    }

    private function checkTwoFactor(): array
    {
        $enabled      = config('services.ra.2fa_enabled');
        $forceAdmins  = config('services.ra.2fa_force_admins');

        if (!$enabled && !$forceAdmins) {
            return $this->result('2fa', 'Two-Factor Auth (2FA)', 'services', 'skipped', 'Deshabilitado (RA_2FA_ENABLED=false).');
        }

        return $this->tryCheck('2fa', 'Two-Factor Auth (2FA)', 'services', function () use ($forceAdmins) {
            // Verificar columnas en ra_users
            $cols = DB::select("SHOW COLUMNS FROM `ra_users` LIKE 'two_factor%'");
            if (count($cols) < 3) {
                throw new \RuntimeException('Columnas two_factor_* no encontradas en ra_users. Ejecuta las migraciones.');
            }

            // Verificar que el paquete Google2FA funciona
            $g2fa = new \PragmaRX\Google2FA\Google2FA();
            $g2fa->generateSecretKey();

            $note = $forceAdmins ? ' · Forzado para admins.' : '';
            return "Paquete google2fa operativo. Columnas en BD presentes.{$note}";
        });
    }

    // -------------------------------------------------------------------------
    // Optional features
    // -------------------------------------------------------------------------

    private function checkOptional(): array
    {
        $checks = [];


        $geoDriver = config('services.maxmind.driver', env('GEOLOCATION_DRIVER'));
        if ($geoDriver && str_starts_with($geoDriver, 'maxmind')) {
            $userId  = config('services.maxmind.user_id',      env('MAXMIND_USER_ID'));
            $license = config('services.maxmind.license_key',  env('MAXMIND_LICENSE_KEY'));
            if ($userId && $license) {
                $checks[] = $this->result('geolocation', 'MaxMind Geolocation', 'optional', 'ok', 'Credenciales configuradas.');
            } else {
                $checks[] = $this->result('geolocation', 'MaxMind Geolocation', 'optional', 'warning', 'Driver maxmind pero credenciales incompletas.');
            }
        } else {
            $checks[] = $this->result('geolocation', 'MaxMind Geolocation', 'optional', 'skipped', 'No configurado.');
        }

        return $checks;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function tryCheck(string $id, string $label, string $category, callable $fn): array
    {
        try {
            $message = $fn();
            return $this->result($id, $label, $category, 'ok', $message);
        } catch (Throwable $e) {
            return $this->result($id, $label, $category, 'error', $e->getMessage());
        }
    }

    private function result(string $id, string $label, string $category, string $status, string $message): array
    {
        return compact('id', 'label', 'category', 'status', 'message');
    }
}
