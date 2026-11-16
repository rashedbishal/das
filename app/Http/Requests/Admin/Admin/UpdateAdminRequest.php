<?php

namespace App\Http\Requests\Admin\Admin;

use App\Rules\BangladeshiPhoneNumber;
use App\Support\FieldLimits;
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
                'name' => ['sometimes', 'required', 'string', FieldLimits::max('admin.name')],
                'password' => ['sometimes', 'nullable', 'string', FieldLimits::max('admin.password'), 'confirmed', Password::default()],
            ];
        }

        return [
            'name' => ['sometimes', 'required', 'string', FieldLimits::max('admin.name')],
            'email' => ['sometimes', 'required', 'string', 'email', FieldLimits::max('admin.email'), Rule::unique('users', 'email')->ignore($admin)],
            'phone' => ['sometimes', 'nullable', new BangladeshiPhoneNumber, Rule::unique('users', 'phone')->ignore($admin)],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
            // Resetting another admin's password; AdminUserService enforces who may do it.
            'password' => ['sometimes', 'nullable', 'string', FieldLimits::max('admin.password'), 'confirmed', Password::default()],
        ];
    }
}
