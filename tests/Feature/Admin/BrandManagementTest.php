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
