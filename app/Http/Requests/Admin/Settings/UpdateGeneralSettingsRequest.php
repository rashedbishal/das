<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageGeneral', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', FieldLimits::max('settings.site_name')],
            'logo' => ['nullable', 'string', FieldLimits::max('settings.logo')],
            'favicon' => ['nullable', 'string', FieldLimits::max('settings.favicon')],
            'contact_email' => ['nullable', 'email', FieldLimits::max('settings.contact_email')],
            'contact_phone' => ['nullable', 'string', FieldLimits::max('settings.contact_phone')],
            'address' => ['nullable', 'string', FieldLimits::max('settings.address')],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', FieldLimits::max('settings.timezone'), 'timezone'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', FieldLimits::max('settings.social_link')],
        ];
    }
}
