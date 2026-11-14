<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use App\Support\FieldLimits;
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
            'delivery_note' => ['sometimes', 'required', 'string', FieldLimits::max('settings.delivery_note')],
            'default_title' => ['nullable', 'string', FieldLimits::max('settings.default_title')],
            'default_description' => ['nullable', 'string', FieldLimits::max('settings.default_description')],
        ];
    }
}
