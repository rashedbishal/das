<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'registration_enabled' => ['required', 'boolean'],
        ];
    }
}
