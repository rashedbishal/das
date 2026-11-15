<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Writes sign-ins, sign-outs, failed sign-ins, lockouts, password resets and email
 * verifications (admins and customers) to the activity log under "auth".
 * Registered for the framework's auth events in AppServiceProvider.
 */
class AuthActivityLogger
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'login']);
        $events->listen(Logout::class, [self::class, 'logout']);
        $events->listen(Failed::class, [self::class, 'failed']);
        $events->listen(Lockout::class, [self::class, 'lockout']);
        $events->listen(PasswordReset::class, [self::class, 'passwordReset']);
        $events->listen(Verified::class, [self::class, 'verified']);
    }

    public function login(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activity->log('auth.login', $event->user, $this->who($event->user).' signed in', [
            'user_type' => $event->user->user_type,
            'remember' => $event->remember,
        ], causer: $event->user);
    }

    public function logout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activity->log('auth.logout', $event->user, $this->who($event->user).' signed out', [
            'user_type' => $event->user->user_type,
        ], causer: $event->user);
    }

    /**
     * Only the identifier that was typed is kept, never the password.
     */
    public function failed(Failed $event): void
    {
        $identifier = $this->identifier($event->credentials);
        $user = $event->user instanceof User ? $event->user : null;

        $this->activity->log('auth.login_failed', $user, 'Failed sign-in attempt'.($identifier !== null ? " for {$identifier}" : ''), [
            'identifier' => $identifier,
            'admin_login' => request()->is('admin/*'),
        ]);
    }

    public function lockout(Lockout $event): void
    {
        $identifier = $this->identifier($event->request->only(['email', 'phone', 'identifier']));

        $this->activity->log('auth.lockout', null, 'Too many sign-in attempts; temporarily locked out'.($identifier !== null ? " ({$identifier})" : ''), [
            'identifier' => $identifier,
            'admin_login' => $event->request->is('admin/*'),
        ]);
    }

    public function passwordReset(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activity->log('auth.password_reset', $event->user, $this->who($event->user).' reset their password', causer: $event->user);
    }

    public function verified(Verified $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->activity->log('auth.email_verified', $event->user, $this->who($event->user).' verified their email address', causer: $event->user);
    }

    private function who(User $user): string
    {
        return ($user->isAdmin() ? 'Admin ' : 'Customer ').$user->name;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function identifier(array $credentials): ?string
    {
        foreach (['email', 'phone', 'identifier'] as $key) {
            if (isset($credentials[$key]) && is_string($credentials[$key]) && $credentials[$key] !== '') {
                return mb_substr($credentials[$key], 0, 255);
            }
        }

        return null;
    }
}
