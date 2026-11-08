<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageCatalogSeo', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page_size' => ['required', 'integer', 'min:1', 'max:100'],
            'featured_limit' => ['required', 'integer', 'min:1', 'max:50'],
            'delivery_note' => ['sometimes', 'required', 'string', 'max:255'],
            'default_title' => ['nullable', 'string', 'max:255'],
            'default_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
