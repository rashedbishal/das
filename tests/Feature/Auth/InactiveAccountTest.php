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
