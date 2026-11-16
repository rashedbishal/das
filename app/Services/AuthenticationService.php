<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * Find the account an identifier and password belong to, or null when they don't match.
     * A deactivated account is still returned, so callers can tell the user why they can't
     * sign in (only once the password is correct) via rejectIfDeactivated().
     */
    public function resolveUser(string $identifier, string $password): ?User
    {
        $identifier = trim($identifier);
        $looksLikeEmail = str_contains($identifier, '@');

        $user = $looksLikeEmail
            ? User::query()->where('email', $identifier)->first()
            : User::query()->where('phone', PhoneNumber::normalizeBangladeshi($identifier))->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        if ($user->isCustomer()) {
            $mode = $this->settings->loginMode();

            if ($looksLikeEmail && ! in_array($mode, ['email', 'both'], true)) {
                return null;
            }

            if (! $looksLikeEmail && ! in_array($mode, ['phone', 'both'], true)) {
                return null;
            }
        }

        return $user;
    }

    /**
     * Stop a deactivated account from signing in with a clear message instead of the
     * generic "credentials do not match" error, and record the attempt.
     *
     * @throws ValidationException
     */
    public function rejectIfDeactivated(User $user, string $field, string $identifier): void
    {
        if ($user->is_active) {
            return;
        }

        event(new Failed('web', $user, [$field => $identifier]));

        throw ValidationException::withMessages([
            $field => 'Your account has been deactivated. Please contact us if you think this is a mistake.',
        ]);
    }
}
