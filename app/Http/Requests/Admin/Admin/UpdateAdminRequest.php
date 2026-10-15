<?php

namespace App\Http\Requests\Admin\Admin;

use App\Rules\BangladeshiPhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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

        if ($this->user()?->is($admin)) {
            // Self-editing admins (Mother Admin included) may only change their name and password.
            return [
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'password' => ['sometimes', 'nullable', 'string', 'confirmed', Password::default()],
            ];
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin)],
            'phone' => ['sometimes', 'nullable', new BangladeshiPhoneNumber, Rule::unique('users', 'phone')->ignore($admin)],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
        ];
    }
}
