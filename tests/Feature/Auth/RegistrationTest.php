<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new customers are signed in and asked for their email verification code', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));
});

test('new customers start unverified', function () {
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'unverified@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->where('email', 'unverified@example.com')->first()->hasVerifiedEmail())->toBeFalse();
});
