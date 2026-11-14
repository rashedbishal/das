<?php

namespace App\Http\Requests\Admin\Role;

use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('role')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', FieldLimits::max('role.name')],
            'slug' => ['nullable', 'string', FieldLimits::max('role.slug'), Rule::unique('roles', 'slug')->ignore($this->route('role'))],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ];
    }
}
