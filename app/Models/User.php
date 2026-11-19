<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use App\Notifications\ResetPasswordNotification;
use App\Services\EmailVerificationCodeService;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $phone
 * @property string|null $google_id
 * @property string $user_type
 * @property int|null $role_id
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'password', 'user_type', 'role_id', 'is_active', 'created_by', 'updated_by', 'email_verified_at'])]
#[Hidden(['password', 'google_id', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsModelActivity, Notifiable {
        LogsModelActivity::activityDescription as defaultActivityDescription;
    }

    protected string $activityLogName = 'users';

    /** @var array<int, string> */
    protected array $activityLogExcept = ['password'];

    /**
     * Prices (and price filters) are for signed-in users whose e-mail is verified.
     */
    public function canSeePrices(): bool
    {
        return $this->hasVerifiedEmail();
    }

    /**
     * E-mail a 6-digit verification code (instead of Laravel's link). Called on
     * registration and by the "resend code" button.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationCodeService::class)->send($this);
    }

    /**
     * Send the DAS-branded "forgot password" email. Admins have no "forgot
     * password" flow (another admin resets it, or the Mother Admin is reset
     * with `das:reset-mother-admin`), so nothing is sent to them; the form
     * still shows the usual "link sent" status so it doesn't reveal admin emails.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        if ($this->user_type === 'admin') {
            return;
        }

        try {
            $this->notify(new ResetPasswordNotification($token));
        } catch (Throwable $exception) {
            // A mail failure (e.g. SMTP down) becomes a form error instead of a 500.
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'We couldn’t send the reset email just now. Please try again in a few minutes.',
            ]);
        }
    }

    protected function activityType(): string
    {
        return $this->user_type === 'admin' ? 'admin' : 'customer';
    }

    /**
     * A customer account created by a signed-out visitor is a self-registration.
     */
    protected function activityDescription(string $eventName): string
    {
        if ($eventName === 'created' && $this->user_type === 'customer' && auth()->guest()) {
            return "Customer {$this->name} registered an account";
        }

        return $this->defaultActivityDescription($eventName);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->user_type === 'customer';
    }

    public function isMotherAdmin(): bool
    {
        return $this->isAdmin() && $this->role?->isMotherAdmin() === true;
    }

    public function hasPermission(string $slug): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        return $this->role?->hasPermission($slug) ?? false;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('user_type', 'admin');
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('user_type', 'customer');
    }

    /**
     * Matches name, email or phone. A term with digits also matches the phone in its
     * stored (normalised) form, so "+880 1712-345678" finds "01712345678".
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $escape = fn (string $value) => '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
        $pattern = $escape($term);
        $digits = preg_replace('/\D+/', '', $term) ?? '';

        return $query->where(function (Builder $inner) use ($pattern, $digits, $term, $escape) {
            $inner->whereRaw("name like ? escape '!'", [$pattern])
                ->orWhereRaw("email like ? escape '!'", [$pattern])
                ->orWhereRaw("phone like ? escape '!'", [$pattern]);

            if (strlen($digits) >= 3) {
                $inner->orWhereRaw("phone like ? escape '!'", [$escape($digits)]);

                $normalized = PhoneNumber::normalizeBangladeshi($term);
                if ($normalized !== null && $normalized !== $digits && preg_match('/^[\d\s()+-]+$/', $term) === 1) {
                    $inner->orWhereRaw("phone like ? escape '!'", [$escape($normalized)]);
                }
            }
        });
    }

    /**
     * Newest accounts first; id breaks ties between accounts created in the same second.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
