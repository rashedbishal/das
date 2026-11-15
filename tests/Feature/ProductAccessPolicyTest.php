<?php

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('guests can view a published product', function () {
    $product = Product::factory()->published()->create();

    expect(Gate::forUser(null)->allows('view', $product))->toBeTrue();
});

test('guests cannot view a draft or archived product', function (string $status) {
    $product = Product::factory()->create(['status' => $status]);

    expect(Gate::forUser(null)->denies('view', $product))->toBeTrue();
})->with(['draft', 'archived']);

test('authenticated customers can view a published product', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->published()->create();

    expect(Gate::forUser($customer)->allows('view', $product))->toBeTrue();
});

test('authenticated customers cannot view a draft or archived product', function (string $status) {
    $customer = User::factory()->create();
    $product = Product::factory()->create(['status' => $status]);

    expect(Gate::forUser($customer)->denies('view', $product))->toBeTrue();
})->with(['draft', 'archived']);

test('admins can view products regardless of status', function (string $status) {
    $admin = User::factory()->admin(Role::factory()->create())->create();
    $product = Product::factory()->create(['status' => $status]);

    expect(Gate::forUser($admin)->allows('view', $product))->toBeTrue();
})->with(['draft', 'published', 'archived']);
