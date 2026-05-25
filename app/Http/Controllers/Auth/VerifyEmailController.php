<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Verifica el email del usuario sin requerir sesión activa.
     * Funciona aunque el link se abra en un navegador distinto al que inició sesión.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        // El middleware 'signed' ya validó la firma de la URL.
        // Aquí verificamos que el hash coincida con el email actual del usuario.
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Si el usuario YA está logueado en este navegador → al dashboard
        if ($request->user() && $request->user()->id == $user->id) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        // Si abrió el link en otro navegador → al login con mensaje de éxito
        // El locale viene del registro del usuario, no de la sesión (puede ser otro navegador)
        if ($user->locale) {
            app()->setLocale($user->locale);
        }

        return redirect()->route('login')
            ->with('status', __('Your email has been verified. You can now log in.'));
    }
}
