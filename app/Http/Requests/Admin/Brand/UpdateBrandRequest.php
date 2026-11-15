<?php

namespace App\Http\Requests\Admin\Brand;

use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('brand')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', FieldLimits::max('brand.name')],
            'slug' => ['nullable', 'string', FieldLimits::max('brand.slug')],
            'logo' => ['nullable', 'string', FieldLimits::max('brand.logo')],
            'description' => ['nullable', 'string', FieldLimits::max('brand.description')],
            'seo_title' => ['nullable', 'string', FieldLimits::max('brand.seo_title')],
            'seo_description' => ['nullable', 'string', FieldLimits::max('brand.seo_description')],
            'website_url' => ['nullable', 'url:http,https', FieldLimits::max('brand.website_url')],
            'is_active' => ['boolean'],
        ];
    }
}
