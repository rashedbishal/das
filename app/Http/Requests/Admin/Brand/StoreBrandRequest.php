<?php

namespace App\Http\Requests\Admin\Brand;

use App\Models\Brand;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Brand::class) ?? false;
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
            'website_url' => ['nullable', 'url', FieldLimits::max('brand.website_url')],
            'is_active' => ['boolean'],
        ];
    }
}
