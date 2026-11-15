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
     * Admins often paste the whole verification tag
     * (<meta name="google-site-verification" content="abc123" />); keep just the code.
     */
    protected function prepareForValidation(): void
    {
        foreach (['google_site_verification', 'bing_site_verification'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $match) === 1
                    ? trim($match[1])
                    : trim($value)]);
            }
        }
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
            'google_site_verification' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_\-]+$/', FieldLimits::max('settings.site_verification')],
            'bing_site_verification' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_\-]+$/', FieldLimits::max('settings.site_verification')],
        ];
    }
}
