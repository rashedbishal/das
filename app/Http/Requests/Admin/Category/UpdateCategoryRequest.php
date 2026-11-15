<?php

namespace App\Http\Requests\Admin\Category;

use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('category')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', FieldLimits::max('category.name')],
            'slug' => ['nullable', 'string', FieldLimits::max('category.slug')],
            'description' => ['nullable', 'string', FieldLimits::max('category.description')],
            'seo_title' => ['nullable', 'string', FieldLimits::max('category.seo_title')],
            'seo_description' => ['nullable', 'string', FieldLimits::max('category.seo_description')],
            'image' => ['nullable', 'string', FieldLimits::max('category.image')],
            'image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:'.FieldLimits::get('number.sort_order')],
        ];
    }
}
