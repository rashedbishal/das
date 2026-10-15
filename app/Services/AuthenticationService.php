<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;

class AuthenticationService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function resolveUser(string $identifier, string $password): ?User
    {
        $identifier = trim($identifier);
        $looksLikeEmail = str_contains($identifier, '@');

        $user = $looksLikeEmail
            ? User::query()->where('email', $identifier)->first()
            : User::query()->where('phone', PhoneNumber::normalizeBangladeshi($identifier))->first();

        if (! $user || ! $user->is_active || ! Hash::check($password, (string) $user->password)) {
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
}
