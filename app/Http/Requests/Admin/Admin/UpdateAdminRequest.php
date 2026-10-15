<?php

namespace App\Http\Requests\Admin\Admin;

use App\Rules\BangladeshiPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('admin')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $admin = $this->route('admin');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin)],
            'phone' => ['sometimes', 'nullable', new BangladeshiPhoneNumber, Rule::unique('users', 'phone')->ignore($admin)],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
        ];
    }
}
