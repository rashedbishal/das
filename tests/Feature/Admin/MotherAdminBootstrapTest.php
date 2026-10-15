<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('das.mother_admin', [
        'name' => 'Ada Admin',
        'email' => 'ada@example.com',
        'password' => 'correct-horse-battery-staple',
    ]);
});

test('creates the mother admin and seeds roles when none exist yet', function () {
    expect(Role::query()->count())->toBe(0);

    $this->artisan('das:bootstrap-mother-admin')->assertSuccessful();

    $motherRole = Role::query()->where('slug', Role::MOTHER_ADMIN)->firstOrFail();
    $admin = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($admin->user_type)->toBe('admin')
        ->and($admin->role_id)->toBe($motherRole->id)
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->isMotherAdmin())->toBeTrue();
});

test('refuses to create a second mother admin', function () {
    User::factory()->motherAdmin()->create();

    $this->artisan('das:bootstrap-mother-admin')->assertFailed();

    expect(User::query()->admins()->count())->toBe(1);
});

test('rejects bootstrap with an invalid email', function () {
    Config::set('das.mother_admin.email', 'not-an-email');

    $this->artisan('das:bootstrap-mother-admin')->assertFailed();

    expect(User::query()->count())->toBe(0);
});
