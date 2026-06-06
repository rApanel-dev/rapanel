<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class ConsoleController extends Controller
{
    private const VALID_SERVERS = ['login', 'char', 'map', 'web'];

    public function index(): Response
    {
        $wsSecret        = config('services.ra.ws_secret');
        [, $wsPublicUrl] = $this->resolveWsUrls();

        return Inertia::render('Admin/Console', [
            'wsPublicUrl' => $wsPublicUrl,
            'wsEnabled'   => ! empty($wsSecret),
        ]);
    }

    // Issues a short-lived JWT for the WebSocket connection
    public function token(): JsonResponse
    {
        $secret = config('services.ra.ws_secret');

        if (empty($secret)) {
            return response()->json(['error' => 'ws-server not configured'], 503);
        }

        return response()->json(['token' => $this->generateWsToken($secret)]);
    }

    public function serverStatus(): JsonResponse
    {
        return $this->wsRequest('get', '/api/status');
    }

    public function serverStart(string $name): JsonResponse
    {
        $this->validateServer($name);
        return $this->wsRequest('post', "/api/{$name}/start");
    }

    public function serverStop(string $name): JsonResponse
    {
        $this->validateServer($name);
        return $this->wsRequest('post', "/api/{$name}/stop");
    }

    public function serverRestart(string $name): JsonResponse
    {
        $this->validateServer($name);
        return $this->wsRequest('post', "/api/{$name}/restart");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function validateServer(string $name): void
    {
        if (! in_array($name, self::VALID_SERVERS, true)) {
            abort(404);
        }
    }

    private function wsRequest(string $method, string $path): JsonResponse
    {
        [$internalUrl] = $this->resolveWsUrls();
        $secret        = config('services.ra.ws_secret');

        if (empty($internalUrl) || empty($secret)) {
            return response()->json(['error' => 'ws-server not configured'], 503);
        }

        try {
            $response = Http::timeout(8)
                ->withToken($secret)
                ->{$method}("{$internalUrl}{$path}");

            return response()->json($response->json(), $response->status());
        } catch (\Exception) {
            return response()->json(['error' => 'ws-server unreachable'], 503);
        }
    }

    // Deriva las URLs del ws-server a partir de RA_LOGIN_IP + RA_WS_PORT.
    // RA_WS_INTERNAL_URL / RA_WS_PUBLIC_URL pueden sobrescribir el auto-detect.
    private function resolveWsUrls(): array
    {
        $host = config('services.ra.login_ip', '127.0.0.1');
        // 0.0.0.0 significa "bind all" en rAthena — para conectar usamos localhost
        $host = ($host === '0.0.0.0') ? '127.0.0.1' : $host;
        $port = config('services.ra.ws_port', 3001);

        $internal = config('services.ra.ws_internal_url') ?? "http://{$host}:{$port}";
        $public   = config('services.ra.ws_public_url')   ?? "ws://{$host}:{$port}";

        return [$internal, $public];
    }

    private function generateWsToken(string $secret): string
    {
        $header  = $this->base64url('{"alg":"HS256","typ":"JWT"}');
        $payload = $this->base64url((string) json_encode([
            'sub' => (string) auth()->id(),
            'exp' => time() + 60,
            'iat' => time(),
        ]));
        $sig = $this->base64url(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$sig}";
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

}
