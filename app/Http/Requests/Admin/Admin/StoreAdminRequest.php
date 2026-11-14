<?php

namespace App\Http\Requests\Admin\Admin;

use App\Models\User;
use App\Rules\BangladeshiPhoneNumber;
use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createAdmin', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', FieldLimits::max('admin.name')],
            'email' => ['required', 'string', 'email', FieldLimits::max('admin.email'), Rule::unique('users', 'email')],
            'phone' => ['nullable', new BangladeshiPhoneNumber, Rule::unique('users', 'phone')],
            'password' => ['required', 'string', FieldLimits::max('admin.password'), Password::default()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
