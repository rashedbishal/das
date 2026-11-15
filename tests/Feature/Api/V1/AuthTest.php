<?php

use App\Models\User;

test('a customer can register through the api', function () {
    $response = $this->withHeader('Referer', 'http://localhost')->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated();
    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('a user can log in through the api with a valid identifier and password', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->withHeader('Referer', 'http://localhost')->postJson(route('api.v1.auth.login'), [
        'identifier' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()->assertJsonPath('data.email', $user->email);
});

test('the api login endpoint rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'identifier' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('identifier');
});

test('an authenticated user can fetch their own user record', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('api.v1.auth.user'));

    $response->assertOk()->assertJsonPath('data.id', $user->id);
});

test('a guest cannot fetch the authenticated user endpoint', function () {
    $this->getJson(route('api.v1.auth.user'))->assertUnauthorized();
});

test('api login attempts are rate limited per account, not shared across accounts on one ip', function () {
    $locked = User::factory()->create(['password' => 'password']);
    $other = User::factory()->create(['password' => 'password']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.auth.login'), ['identifier' => $locked->email, 'password' => 'wrong']);
    }

    $this->postJson(route('api.v1.auth.login'), ['identifier' => $locked->email, 'password' => 'wrong'])
        ->assertTooManyRequests();

    $this->withHeader('Referer', 'http://localhost')
        ->postJson(route('api.v1.auth.login'), ['identifier' => $other->email, 'password' => 'password'])
        ->assertOk();
});

test('api login from a non first-party origin gets a 400 instead of a server error', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->postJson(route('api.v1.auth.login'), ['identifier' => $user->email, 'password' => 'password'])
        ->assertBadRequest();
});
