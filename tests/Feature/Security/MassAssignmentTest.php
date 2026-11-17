<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('a registering customer cannot self-promote to admin or self-verify via extra form fields', function () {
    $this->post(route('register.store'), [
        'name' => 'Attacker',
        'email' => 'attacker@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'user_type' => 'admin',
        'role_id' => 1,
        'is_active' => true,
        'email_verified_at' => now()->toDateTimeString(),
    ]);

    $user = User::query()->where('email', 'attacker@example.com')->firstOrFail();

    expect($user->user_type)->toBe('customer')
        ->and($user->role_id)->toBeNull()
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

test('an admin cannot inject role_id through the category creation payload', function () {
    $admin = adminUser(['categories.manage']);

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Injected Category',
        'created_by' => 999,
    ]);

    $category = Category::query()->where('name', 'Injected Category')->firstOrFail();

    expect($category->getAttributes())->not->toHaveKey('created_by');
});

test('a product update cannot change created_by through the request payload', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['created_by' => null]);

    $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'sku' => $product->sku,
        'regular_price' => $product->regular_price,
        'stock_quantity' => $product->stock_quantity,
        'stock_status' => $product->stock_status,
        'status' => 'draft',
        'created_by' => 999,
    ]);

    expect($product->fresh()->created_by)->not->toBe(999);
});
