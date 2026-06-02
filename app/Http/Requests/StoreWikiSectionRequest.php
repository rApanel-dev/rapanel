<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWikiSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:100'],
            'description'  => ['nullable', 'string', 'max:500'],
            'icon'         => ['nullable', 'string', 'max:10'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ];
    }
}
