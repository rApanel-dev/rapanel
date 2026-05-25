<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmailRequest extends FormRequest
{
    public function rules(): array
    {
        $emailRules = [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'confirmed',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];

        // Con 2FA activo: se requieren contraseña Y código TOTP
        if ($this->user()->hasTwoFactorEnabled()) {
            return array_merge($emailRules, [
                'current_password' => ['required', 'current_password'],
                'totp_code'        => ['required', 'string', 'digits:6'],
            ]);
        }

        return array_merge($emailRules, [
            'current_password' => ['required', 'current_password'],
        ]);
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => __('You must confirm your password to change your email address.'),
            'current_password.current_password' => __('The provided password does not match your current password.'),
            'totp_code.required'                => __('Please enter your authenticator code.'),
            'totp_code.digits'                  => __('The provided two factor authentication code was invalid.'),
            'email.unique'                      => __('This email address is already in use.'),
            'email.confirmed'                   => __('The email confirmation does not match.'),
        ];
    }
}
