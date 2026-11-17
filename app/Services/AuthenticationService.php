<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    /**
     * Find the account an e-mail address and password belong to, or null when they don't
     * match. Sign-in is by e-mail only (customers and admins alike).
     * A deactivated account is still returned, so callers can tell the user why they can't
     * sign in (only once the password is correct) via rejectIfDeactivated().
     */
    public function resolveUser(string $identifier, string $password): ?User
    {
        $email = trim($identifier);

        if (! str_contains($email, '@')) {
            return null;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            return null;
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
