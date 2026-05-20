<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'body'           => ['nullable', 'string'],
            'type'           => ['required', 'integer', 'in:1,2,3'],
            'is_published'   => ['boolean'],
            'is_pinned'      => ['boolean'],
            'allow_comments' => ['boolean'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
