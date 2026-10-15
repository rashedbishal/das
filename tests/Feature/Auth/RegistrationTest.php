<?php

use App\Models\User;
use App\Services\SettingsService;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new customers are left unverified when email verification is required', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'unverified@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'unverified@example.com')->first();

    expect($user->hasVerifiedEmail())->toBeFalse();
});

test('new customers are automatically verified when email verification is disabled', function () {
    app(SettingsService::class)->set('auth', 'require_email_verification', false);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'auto-verified@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'auto-verified@example.com')->first();

    expect($user->hasVerifiedEmail())->toBeTrue();
});
