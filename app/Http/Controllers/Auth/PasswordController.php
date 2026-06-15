<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user     = $request->user();
        $rules    = [
            'current_password' => ['required', 'current_password'],
            'password'         => [
                'required',
                Password::defaults(),
                'confirmed',
                function ($attribute, $value, $fail) use ($request) {
                    if (Hash::check($value, $request->user()->password)) {
                        $fail(__('Your new password must be different from your current password.'));
                    }
                },
            ],
        ];
        $messages = [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ];

        if ($user->hasTwoFactorEnabled()) {
            $rules['totp_code']             = ['required', 'string', 'digits:6'];
            $messages['totp_code.required'] = __('Please enter your authenticator code.');
            $messages['totp_code.digits']   = __('The provided two factor authentication code was invalid.');
        }

        $validated = $request->validate($rules, $messages);

        if ($user->hasTwoFactorEnabled()) {
            $valid = (new Google2FA())->verifyKey(decrypt($user->two_factor_secret), $request->totp_code, 2);
            if (! $valid) {
                return back()->withErrors(['totp_code' => __('The provided two factor authentication code was invalid.')]);
            }
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActionLog::create([
            'user_id'    => $user->id,
            'category'   => 'MASTER_ACCOUNT',
            'action'     => 'password_changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata'   => null,
        ]);

        if (config('services.ra.require_email_verify', false)) {
            $notification = new SecurityAlertNotification(
                'password_changed',
                $request->ip(),
                now()->format('d/m/Y H:i:s'),
            );

            if ($user->locale) {
                $notification = $notification->locale($user->locale);
            }

            $user->notify($notification);
        }

        return back();
    }
}
