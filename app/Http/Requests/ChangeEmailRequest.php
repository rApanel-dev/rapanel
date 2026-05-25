<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'confirmed',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'current_password' => ['required', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => __('You must confirm your password to change your email address.'),
            'current_password.current_password' => __('The provided password does not match your current password.'),
            'email.unique'                      => __('This email address is already in use.'),
            'email.confirmed'                   => __('The email confirmation does not match.'),
        ];
    }
}
