<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
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
            ],
            [
                'current_password.current_password' => __('The provided password does not match your current password.'),
            ]
        );

        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['password']),
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
