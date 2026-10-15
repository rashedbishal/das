<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageAuth', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login_mode' => ['required', Rule::in(['email', 'phone', 'both'])],
            'registration_enabled' => ['required', 'boolean'],
        ];
    }
}
