<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'user_type' => 'customer',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static {}

    public function admin(?Role $role = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'role_id' => $role === null ? Role::factory() : $role->id,
        ]);
    }

    public function motherAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'role_id' => Role::factory()->motherAdmin(),
        ]);
    }

    public function withPhone(?string $phone = null): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $phone ?? '01'.fake()->numberBetween(3, 9).fake()->numerify('########'),
        ]);
    }
}
