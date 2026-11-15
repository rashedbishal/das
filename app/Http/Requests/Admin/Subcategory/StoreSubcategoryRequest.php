<?php

namespace App\Http\Requests\Admin\Subcategory;

use App\Models\Subcategory;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Subcategory::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', FieldLimits::max('subcategory.name')],
            'slug' => ['nullable', 'string', FieldLimits::max('subcategory.slug')],
            'description' => ['nullable', 'string', FieldLimits::max('subcategory.description')],
            'seo_title' => ['nullable', 'string', FieldLimits::max('subcategory.seo_title')],
            'seo_description' => ['nullable', 'string', FieldLimits::max('subcategory.seo_description')],
            'image' => ['nullable', 'string', FieldLimits::max('subcategory.image')],
            'image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:'.FieldLimits::get('number.sort_order')],
        ];
    }
}
