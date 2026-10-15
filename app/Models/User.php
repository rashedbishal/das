<?php

namespace App\Models;

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

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
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
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
}
