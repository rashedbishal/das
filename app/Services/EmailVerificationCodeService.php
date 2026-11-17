<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Six-digit e-mail verification codes for new customer accounts.
 *
 * Each user has at most one pending code, stored hashed. A code expires after
 * CODE_LIFETIME_MINUTES and stops working after MAX_ATTEMPTS wrong guesses;
 * the customer then asks for a new one (resending replaces the old code).
 *
 * Bound as a scoped service (one instance per request), so the responses
 * after registration and "send a new code" can ask whether sending failed.
 */
class EmailVerificationCodeService
{
    public const CODE_LIFETIME_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    private bool $lastSendFailed = false;

    /**
     * Create a fresh code for the user (replacing any pending one) and e-mail it.
     * A mail failure (e.g. SMTP down) is reported to the logs instead of breaking
     * registration; the customer can press "Send a new code" once it works again.
     *
     * @return bool whether the e-mail was handed to the mailer
     */
    public function send(User $user): bool
    {
        $this->lastSendFailed = false;

        if ($user->hasVerifiedEmail() || $user->email === null) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
            ],
        );

        try {
            $user->notify(new VerifyEmailCodeNotification($code, self::CODE_LIFETIME_MINUTES));
        } catch (Throwable $exception) {
            report($exception);
            $this->lastSendFailed = true;

            return false;
        }

        return true;
    }

    /**
     * Whether the most recent send() in this request could not e-mail the code.
     */
    public function lastSendFailed(): bool
    {
        return $this->lastSendFailed;
    }

    /**
     * Mark the user's e-mail as verified when the code matches.
     *
     * @throws ValidationException when the code is wrong, expired or used up
     */
    public function verify(User $user, string $code, string $field = 'code'): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $pending = EmailVerificationCode::query()->where('user_id', $user->id)->first();
        $code = preg_replace('/\D/', '', $code) ?? '';

        if ($pending === null || $pending->isExpired() || $pending->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                $field => 'This code has expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($code, $pending->code_hash)) {
            $pending->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $pending->attempts;

            throw ValidationException::withMessages([
                $field => $remaining > 0
                    ? "That code isn't right. You have {$remaining} ".($remaining === 1 ? 'try' : 'tries').' left.'
                    : 'Too many wrong codes. Please request a new one.',
            ]);
        }

        $pending->delete();

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
    }
}
