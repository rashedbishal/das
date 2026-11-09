<?php

use App\Models\Brand;
use App\Models\Product;

test('an admin with brands.manage can create a brand', function () {
    $admin = adminUser(['brands.manage']);

    $response = $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Dentsply Sirona',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.brands.index'));
    expect(Brand::query()->where('slug', 'dentsply-sirona')->exists())->toBeTrue();
});

test('an admin without brands.manage cannot create a brand', function () {
    $admin = adminUser(['brands.view']);

    $response = $this->actingAs($admin)->post(route('admin.brands.store'), ['name' => 'Dentsply Sirona']);

    $response->assertForbidden();
});

test('a brand cannot be deleted while products are assigned to it', function () {
    $admin = adminUser(['brands.manage']);
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);

    $response = $this->actingAs($admin)->delete(route('admin.brands.destroy', $brand));

    $response->assertSessionHasErrors('brand');
});

test('creating a brand validates the website url', function () {
    $admin = adminUser(['brands.manage']);

    $response = $this->actingAs($admin)->post(route('admin.brands.store'), [
        'name' => 'Acme',
        'website_url' => 'not-a-url',
    ]);

    $response->assertSessionHasErrors('website_url');
});

test('an admin can activate brands in bulk', function () {
    $admin = adminUser(['brands.manage']);
    $brands = Brand::factory()->count(2)->create(['is_active' => false]);

    $response = $this->actingAs($admin)->post(route('admin.brands.bulk'), [
        'ids' => $brands->modelKeys(),
        'action' => 'activate',
    ]);

    $response->assertRedirect(route('admin.brands.index'));
    expect(Brand::query()->whereKey($brands->modelKeys())->where('is_active', false)->count())->toBe(0);
});

test('bulk brand deletion rejects the entire selection if a brand has products', function () {
    $admin = adminUser(['brands.manage']);
    $brands = Brand::factory()->count(2)->create();
    Product::factory()->create(['brand_id' => $brands[1]->id]);

    $response = $this->actingAs($admin)->post(route('admin.brands.bulk'), [
        'ids' => $brands->modelKeys(),
        'action' => 'delete',
    ]);

    $response->assertSessionHasErrors('ids');
    expect(Brand::query()->whereKey($brands->modelKeys())->count())->toBe(2);
});
