<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\ClientCatalogSeeder;

test('client catalog import replaces catalog records while preserving users', function () {
    $user = User::factory()->create();
    Product::factory()->create(['sku' => 'DEMO-OLD-0001']);
    Tag::factory()->create();

    $seeder = new ClientCatalogSeeder;
    $seeder->run();
    $seeder->run();

    expect($user->fresh())->not->toBeNull()
        ->and(Product::query()->count())->toBe(15)
        ->and(ProductVariant::query()->count())->toBe(81)
        ->and(Category::query()->orderBy('id')->pluck('name')->all())->toBe(['Instrument', 'Material'])
        ->and(Subcategory::query()->orderBy('id')->pluck('name')->all())->toBe([
            'Porceline',
            'Denture',
            'cad-cam-material',
        ])
        ->and(Brand::query()->orderBy('id')->pluck('name')->all())->toBe(['DAS', 'SHOFU'])
        ->and(Tag::query()->count())->toBe(0)
        ->and(Product::query()->where('sku', 'DEMO-OLD-0001')->exists())->toBeFalse()
        ->and(Product::query()->whereNull('regular_price')->count())->toBe(15)
        ->and(ProductVariant::query()->whereNull('price')->count())->toBe(81)
        ->and(Product::query()->whereNull('subcategory_id')->count())->toBe(8)
        ->and(Setting::query()->where('group', 'catalog')->where('key', 'delivery_note')->value('value'))
        ->toBe('Through courier all over Bangladesh');

    foreach (Product::query()->with('media')->get() as $product) {
        expect($product->media)->toHaveCount(1)
            ->and($product->media->first()->url)->toStartWith('/images/client-catalog/');
        expect(is_file(public_path(ltrim($product->media->first()->url, '/'))))->toBeTrue();
    }
});
