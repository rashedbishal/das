<?php

namespace App\Http\Requests\Admin\Brand;

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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }
}
