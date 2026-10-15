<?php

namespace App\Http\Requests\Admin\Admin;

use App\Models\User;
use App\Rules\BangladeshiPhoneNumber;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', new BangladeshiPhoneNumber, Rule::unique('users', 'phone')],
            'password' => ['required', 'string', Password::default()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
