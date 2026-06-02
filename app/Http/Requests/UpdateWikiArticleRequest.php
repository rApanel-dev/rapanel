<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWikiArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'section_id'     => ['required', 'exists:wiki_sections,id'],
            'title'          => ['required', 'string', 'max:200'],
            'content'        => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048'],
            'focal_x'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'focal_y'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order'     => ['nullable', 'integer', 'min:0'],
            'show_toc'       => ['boolean'],
            'is_published'   => ['boolean'],
        ];
    }
}
