<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Rules\BangladeshiPhoneNumber;
use App\Services\SettingsService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Validate and create a newly registered user.
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

        $mode = $this->settings->loginMode();
        $input['phone'] = PhoneNumber::normalizeBangladeshi($input['phone'] ?? null);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules($mode),
            'phone' => $this->phoneRules($mode),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'password' => $input['password'],
            'user_type' => 'customer',
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function emailRules(string $mode): array
    {
        return match ($mode) {
            'phone' => ['prohibited'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            default => ['required_without:phone', 'nullable', 'string', 'email', 'max:255', Rule::unique(User::class)],
        };
    }

    /**
     * @return array<int, mixed>
     */
    private function phoneRules(string $mode): array
    {
        return match ($mode) {
            'email' => ['prohibited'],
            'phone' => ['required', new BangladeshiPhoneNumber, Rule::unique(User::class)],
            default => ['required_without:email', 'nullable', new BangladeshiPhoneNumber, Rule::unique(User::class)],
        };
    }
}
