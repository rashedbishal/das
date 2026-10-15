<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use App\Rules\BangladeshiPhoneNumber;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsappSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageWhatsapp', Setting::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalizeBangladeshi($this->input('phone')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'phone' => ['required_if:enabled,true', 'nullable', new BangladeshiPhoneNumber],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
