<?php

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;

beforeEach(function () {
    app(SettingsService::class)->set('auth', 'registration_enabled', true);
});

test('registers a customer with email only when login mode is email', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'email');

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    expect($user->phone)->toBeNull();
});

test('rejects a phone number during registration when login mode is email', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'email');

    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '01712345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('registers a customer with a valid bangladeshi phone when login mode is phone', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'phone');

    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'phone' => '+880 1712-345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $user = User::query()->where('phone', '01712345678')->firstOrFail();
    expect($user->email)->toBeNull();
});

test('rejects a phone number that is not 11 digits', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'phone');

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'phone' => '0171234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('rejects a phone number with a disallowed prefix', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'phone');

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'phone' => '01212345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('prevents duplicate accounts for equivalent normalized phone numbers', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'phone');
    User::factory()->create(['phone' => '01712345678', 'email' => null]);

    $response = $this->post(route('register.store'), [
        'name' => 'Duplicate',
        'phone' => '+8801712345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('phone');
});

test('allows registering with either identifier when login mode is both', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'both');

    $this->post(route('register.store'), [
        'name' => 'Either Or',
        'phone' => '01812345678',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('a customer can log in with a phone number in a different format than it was stored', function () {
    User::factory()->create(['phone' => '01712345678', 'email' => null, 'password' => 'password']);

    $this->post(route('login.store'), [
        'email' => '+880 171-234-5678',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('an inactive customer cannot log in', function () {
    User::factory()->create(['email' => 'inactive@example.com', 'password' => 'password', 'is_active' => false]);

    $this->post(route('login.store'), [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('a customer cannot log in by phone when login mode is restricted to email', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'email');
    User::factory()->create(['phone' => '01712345678', 'email' => 'dual@example.com', 'password' => 'password']);

    $this->post(route('login.store'), [
        'email' => '01712345678',
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('an admin can always log in with email even when customer login mode is phone only', function () {
    app(SettingsService::class)->set('auth', 'login_mode', 'phone');
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
