<?php

namespace App\Http\Requests\Admin\Brand;

use App\Services\BrandService;
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
            'logo_file' => [
                'nullable',
                'file',
                'image',
                'mimes:'.implode(',', BrandService::LOGO_FORMATS),
                'max:'.BrandService::MAX_LOGO_UPLOAD_SIZE_KB,
                'dimensions:max_width='.BrandService::MAX_LOGO_SOURCE_DIMENSION.',max_height='.BrandService::MAX_LOGO_SOURCE_DIMENSION,
            ],
            'remove_logo' => ['boolean'],
            'description' => ['nullable', 'string', FieldLimits::max('brand.description')],
            'seo_title' => ['nullable', 'string', FieldLimits::max('brand.seo_title')],
            'seo_description' => ['nullable', 'string', FieldLimits::max('brand.seo_description')],
            'website_url' => ['nullable', 'url:http,https', FieldLimits::max('brand.website_url')],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo_file.max' => 'The logo may not be larger than '.BrandService::MAX_LOGO_UPLOAD_SIZE_KB.' KB.',
            'logo_file.mimes' => 'The logo must be a WebP, PNG or JPG image.',
        ];
    }
}
