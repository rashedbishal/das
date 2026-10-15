<?php

namespace App\Http\Requests\Admin\Settings;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageTracking', Setting::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'head_scripts' => ['nullable', 'string', 'max:20000'],
            'body_scripts' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
