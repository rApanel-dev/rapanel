<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function show(Request $request): Response|RedirectResponse
    {
        if (! config('services.ra.2fa_enabled')) {
            abort(404);
        }

        $user = $request->user();

        // Generar secret si aún no tiene uno
        if (! $user->two_factor_secret) {
            $user->forceFill([
                'two_factor_secret' => encrypt($this->google2fa->generateSecretKey()),
            ])->save();
        }

        $secret = decrypt($user->two_factor_secret);
        $qrUrl  = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Generar el SVG del QR en el servidor
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $qrSvg  = base64_encode($writer->writeString($qrUrl));

        return Inertia::render('Profile/TwoFactor', [
            'qrCode'    => 'data:image/svg+xml;base64,' . $qrSvg,
            'secretKey' => $secret,
            'enabled'   => $user->hasTwoFactorEnabled(),
            'recoveryCodes' => $user->hasTwoFactorEnabled() ? $user->twoFactorRecoveryCodes() : [],
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        if (! config('services.ra.2fa_enabled')) {
            abort(404);
        }

        $request->validate(['code' => ['required', 'string', 'digits:6']]);

        $user   = $request->user();
        $secret = decrypt($user->two_factor_secret);

        if (! $this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => __('The provided two factor authentication code was invalid.')]);
        }

        $codes = collect(range(1, 8))->map(fn() => Str::random(10) . '-' . Str::random(10))->all();

        $user->forceFill([
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        ActionLog::create([
            'user_id'    => $user->id,
            'category'   => 'MASTER_ACCOUNT',
            'action'     => 'two_factor_enabled',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => null,
        ]);

        return redirect()->route('two-factor.show')
            ->with('status', '2fa-enabled');
    }

    public function disable(Request $request): RedirectResponse
    {
        if (! config('services.ra.2fa_enabled')) {
            abort(404);
        }

        $request->validate(
            ['password' => ['required', 'current_password']],
            ['password.current_password' => __('The provided password does not match your current password.')]
        );

        $user = $request->user();

        // No permitir desactivar si es admin con 2FA forzado
        if (config('services.ra.2fa_force_admins') && $user->role === 'Admin') {
            return back()->withErrors(['password' => __('Administrators are required to keep two factor authentication enabled.')]);
        }

        // Validar código TOTP o código de recuperación para confirmar la desactivación
        $validTotp     = $request->filled('code') && $this->google2fa->verifyKey(decrypt($user->two_factor_secret), $request->code, 2);
        $validRecovery = false;

        if (! $validTotp && $request->filled('recovery_code')) {
            $codes = $user->twoFactorRecoveryCodes();
            $index = array_search($request->recovery_code, $codes, true);
            if ($index !== false) {
                $validRecovery = true;
                unset($codes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
                ])->save();
            }
        }

        if (! $validTotp && ! $validRecovery) {
            return back()->withErrors(['code' => __('The provided two factor authentication code was invalid.')]);
        }

        $user->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        ActionLog::create([
            'user_id'    => $user->id,
            'category'   => 'MASTER_ACCOUNT',
            'action'     => 'two_factor_disabled',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => null,
        ]);

        return redirect()->route('two-factor.show')
            ->with('status', '2fa-disabled');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        if (! config('services.ra.2fa_enabled') || ! $request->user()->hasTwoFactorEnabled()) {
            abort(404);
        }

        $codes = collect(range(1, 8))->map(fn() => Str::random(10) . '-' . Str::random(10))->all();

        $request->user()->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();

        return redirect()->route('two-factor.show')
            ->with('status', '2fa-recovery-regenerated');
    }
}
