<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class AdminTwoFactorVerifyController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! config('services.ra.2fa_force_admins')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/TwoFactorVerify');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user  = $request->user();
        $valid = (new Google2FA())->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code,
            2
        );

        if (! $valid) {
            return back()->withErrors(['code' => __('The provided two factor authentication code was invalid.')]);
        }

        $request->session()->put('two_factor_verified_at', now()->timestamp);

        return redirect()->intended(route('admin.dashboard'));
    }
}
