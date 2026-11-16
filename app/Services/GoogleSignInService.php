<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * "Continue with Google" for customers. A Google account is matched by its stable
 * Google id first, then linked to an existing customer with the same verified
 * email, and otherwise becomes a new (password-less) customer. Admins never sign
 * in this way: they keep using /admin/login.
 */
class GoogleSignInService
{
    public const UNUSABLE_ACCOUNT = 'This Google account can\'t be used to sign in here.';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuthenticationService $authentication,
    ) {}

    public function isEnabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /**
     * @throws ValidationException when the Google account can't sign in as a customer.
     */
    public function customerFor(SocialiteUser $googleUser): User
    {
        $googleId = (string) $googleUser->getId();
        $email = strtolower(trim((string) $googleUser->getEmail()));

        if ($googleId === '' || $email === '' || ! $this->emailIsVerified($googleUser)) {
            $this->fail('Your Google account doesn\'t have a verified email address.');
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? $this->linkExistingCustomer($email, $googleId)
            ?? $this->registerCustomer($googleUser, $email, $googleId);

        if (! $user->isCustomer()) {
            $this->fail(self::UNUSABLE_ACCOUNT);
        }

        $this->authentication->rejectIfDeactivated($user, 'email', $email);

        return $user;
    }

    private function linkExistingCustomer(string $email, string $googleId): ?User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return null;
        }

        if (! $user->isCustomer()) {
            $this->fail(self::UNUSABLE_ACCOUNT);
        }

        if ($user->google_id !== null && $user->google_id !== $googleId) {
            $this->fail('This email is already linked to a different Google account.');
        }

        // Google has verified the address, so the existing account's email is confirmed too.
        $user->forceFill([
            'google_id' => $googleId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    private function registerCustomer(SocialiteUser $googleUser, string $email, string $googleId): User
    {
        if (! $this->settings->registrationEnabled()) {
            $this->fail('Registration is currently disabled.');
        }

        $user = new User([
            'name' => trim((string) $googleUser->getName()) ?: Str::before($email, '@'),
            'email' => $email,
            'user_type' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->forceFill(['google_id' => $googleId])->save();

        event(new Registered($user));

        return $user;
    }

    private function emailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];

        return ($raw['email_verified'] ?? false) === true;
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['email' => $message]);
    }
}
