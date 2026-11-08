<?php

use App\Models\Product;
use Illuminate\Database\QueryException;

test('products can have variants with on-order stock by default', function () {
    $product = Product::factory()->create(['regular_price' => null]);

    $variant = $product->variants()->create([
        'name' => 'SS Pins',
        'sku' => 'DAS-0001',
        'price' => null,
    ]);
    $variant = $variant->fresh();

    expect($product->fresh()->variants)->toHaveCount(1)
        ->and($variant->product->is($product))->toBeTrue()
        ->and($variant->stock_quantity)->toBe(0)
        ->and($variant->stock_status)->toBe('on_order')
        ->and($variant->is_active)->toBeTrue()
        ->and($variant->price)->toBeNull();
});

test('product price may be null and effective price remains null', function () {
    $product = Product::factory()->create(['regular_price' => null]);

    expect($product->regular_price)->toBeNull()
        ->and($product->effectivePrice())->toBeNull();
});

test('variant skus may be null but non-null skus are unique', function () {
    $product = Product::factory()->create();

    $product->variants()->create(['name' => 'No SKU']);
    $product->variants()->create(['name' => 'Another no SKU']);
    $product->variants()->create(['name' => 'With SKU', 'sku' => 'DAS-0001']);

    expect(fn () => $product->variants()->create([
        'name' => 'Duplicate SKU',
        'sku' => 'DAS-0001',
    ]))->toThrow(QueryException::class);
});
