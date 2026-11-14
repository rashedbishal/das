<?php

use App\Models\User;

test('a guest sees the inline sign-in experience on a product URL', function () {
    $response = $this->get(route('products.show', 'any-product'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('storefront/Product')
        ->where('loginMode', 'both'));
});

test('a customer returns to the requested product URL after signing in', function () {
    $customer = User::factory()->create(['password' => 'password']);

    $this->get(route('products.show', 'any-product'));

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('products.show', 'any-product', absolute: false));
});
