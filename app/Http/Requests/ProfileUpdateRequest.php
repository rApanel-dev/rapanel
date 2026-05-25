<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'country'   => ['nullable', 'string', 'size:2'],
            'locale'    => ['nullable', 'string', 'in:en,es,pt,fr'],
        ];
    }
}
