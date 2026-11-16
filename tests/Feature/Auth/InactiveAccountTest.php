<?php

use App\Models\User;

test('a customer deactivated mid-session is logged out on their next request', function () {
    $customer = User::factory()->create();
    $this->actingAs($customer);
    $customer->update(['is_active' => false]);

    $this->get(route('about'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a deactivated customer loses api access immediately', function () {
    $customer = User::factory()->create();
    $this->actingAs($customer);
    $customer->update(['is_active' => false]);

    $this->getJson(route('api.v1.auth.user'))->assertUnauthorized();
});

test('a deactivated admin is told the account is deactivated at the admin login', function () {
    $admin = adminUser();
    $admin->update(['is_active' => false]);

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact us if you think this is a mistake.']);

    $this->assertGuest();
});

test('a deactivated account only reveals its status once the password is correct', function () {
    $customer = User::factory()->create(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
});

test('a deactivated admin on the customer login gets the generic error', function () {
    $admin = adminUser();
    $admin->update(['is_active' => false]);

    $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
});

test('a deactivated customer is told the account is deactivated by the api login', function () {
    $customer = User::factory()->create(['is_active' => false]);

    $this->postJson(route('api.v1.auth.login'), [
        'identifier' => $customer->email,
        'password' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identifier' => 'Your account has been deactivated.']);

    $this->assertGuest();
});
