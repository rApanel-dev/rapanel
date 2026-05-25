<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user &&
            $user->role === 'Admin' &&
            config('services.ra.2fa_force_admins') &&
            ! $user->hasTwoFactorEnabled()
        ) {
            $allowed = ['two-factor.show', 'two-factor.enable', 'two-factor.recovery-codes', 'logout'];

            if (! in_array($request->route()?->getName(), $allowed)) {
                return redirect()->route('two-factor.show')
                    ->with('status', '2fa-required');
            }
        }

        return $next($request);
    }
}
