<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('two_factor_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget('two_factor_user_id');
            return redirect()->route('login');
        }

        // Verificar código TOTP
        if ($request->filled('code')) {
            $valid = (new Google2FA())->verifyKey(
                decrypt($user->two_factor_secret),
                $request->code,
                2 // window de tolerancia de ±2 períodos (60 segundos)
            );

            if ($valid) {
                return $this->completeLogin($request, $user);
            }
        }

        // Verificar código de recuperación
        if ($request->filled('recovery_code')) {
            $codes = $user->twoFactorRecoveryCodes();
            $index = array_search($request->recovery_code, $codes, true);

            if ($index !== false) {
                // Invalidar el código usado
                unset($codes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
                ])->save();

                return $this->completeLogin($request, $user);
            }
        }

        return back()->withErrors(['code' => __('The provided two factor authentication code was invalid.')]);
    }

    private function completeLogin(Request $request, User $user): RedirectResponse
    {
        $remember = $request->session()->pull('two_factor_remember', false);
        $request->session()->forget('two_factor_user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
