<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Validate and create a newly registered customer. Registration is by e-mail
     * only; the account stays unverified until the customer enters the 6-digit
     * code e-mailed to them (sent by Laravel's Registered event listener).
     *
     * @param  array<string, string|null>  $input
     */
    public function create(array $input): User
    {
        if (! $this->settings->registrationEnabled()) {
            throw ValidationException::withMessages([
                'email' => 'Registration is currently disabled.',
            ]);
        }

        if (is_string($input['email'] ?? null)) {
            $input['email'] = mb_strtolower(trim($input['email']));
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'user_type' => 'customer',
        ]);
    }
}
