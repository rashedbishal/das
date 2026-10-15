<?php

namespace App\Http\Requests\Admin\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransferMotherAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMotherAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
