<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminTwoFactorVerified
{
    private const SESSION_TIMEOUT = 1800; // 30 minutos

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! config('services.ra.2fa_force_admins')) {
            return $next($request);
        }

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            // RequireTwoFactor ya habrá redirigido a setup si corresponde
            return $next($request);
        }

        $verifiedAt = $request->session()->get('two_factor_verified_at');

        if (! $verifiedAt || (now()->timestamp - $verifiedAt) > self::SESSION_TIMEOUT) {
            $request->session()->put('url.intended', $request->url());
            return redirect()->route('admin.verify-2fa');
        }

        return $next($request);
    }
}
