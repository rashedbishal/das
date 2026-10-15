<?php

namespace App\Http\Requests\Admin\HomepageSection;

use App\Models\HomepageSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', HomepageSection::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['banner', 'section'])],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:2048'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'content' => ['nullable', 'array'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
