<?php

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;

beforeEach(function () {
    app(SettingsService::class)->set('auth', 'registration_enabled', true);
});

test('a customer registers with an email address and no phone number', function () {
    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'Jane@Example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    expect($user->phone)->toBeNull()
        ->and($user->user_type)->toBe('customer');
});

test('registration requires an email address', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'phone' => '01712345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a phone number sent with the registration form is ignored', function () {
    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '01712345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->where('email', 'jane@example.com')->value('phone'))->toBeNull();
});

test('an email address can only be registered once, whatever its letter case', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Jane Again',
        'email' => 'JANE@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');
});

test('a customer cannot sign in with a phone number', function () {
    User::factory()->create(['phone' => '01712345678', 'email' => 'dual@example.com', 'password' => 'password']);

    $this->post(route('login.store'), [
        'email' => '01712345678',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('an inactive customer cannot log in and is told the account is deactivated', function () {
    User::factory()->create(['email' => 'inactive@example.com', 'password' => 'password', 'is_active' => false]);

    $this->post(route('login.store'), [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact us if you think this is a mistake.']);

    $this->assertGuest();
});

test('an admin signs in with email', function () {
    User::factory()->admin(Role::factory()->create())->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->post(route('admin.login.store'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('registration is rejected when disabled via settings', function () {
    app(SettingsService::class)->set('auth', 'registration_enabled', false);

    $this->post(route('register.store'), [
        'name' => 'Nope',
        'email' => 'nope@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
});
