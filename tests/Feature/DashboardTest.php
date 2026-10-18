<?php

use App\Models\User;

test('guests are redirected to the admin login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('admin.login'));
});

test('admins can visit the dashboard', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('customers are sent to the storefront instead of the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('home'));
});

test('signed-in users are sent back to their side of the app from the login pages', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('home'));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.login'))
        ->assertRedirect(route('dashboard'));
});
