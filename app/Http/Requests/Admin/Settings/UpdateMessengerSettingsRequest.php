<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMessengerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMessenger', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'page_url' => ['required_if:enabled,true', 'nullable', 'url', 'max:2048'],
        ];
    }
}
