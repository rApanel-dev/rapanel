<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Prioridad: 1) bandera elegida en la sesión (override temporal del usuario),
        //            2) idioma preferido del perfil del usuario logueado,
        //            3) APP_LOCALE del install (fallback por defecto de Laravel).
        if (session()->has('locale')) {
            \App::setLocale(session()->get('locale'));
        } elseif (($user = $request->user()) && $user->locale) {
            \App::setLocale($user->locale);
        }
        
        return $next($request);
    }
}
