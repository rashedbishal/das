<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\FieldLimits;

function productPayload(array $overrides = []): array
{
    return [
        'name' => 'Dental Chair',
        'sku' => 'SKU-LIMITS',
        'status' => 'draft',
        'stock_quantity' => 0,
        'stock_status' => 'on_order',
        'category_id' => Category::factory()->create()->id,
        ...$overrides,
    ];
}

test('field limits are read from the shared JSON file', function () {
    $json = json_decode((string) file_get_contents(resource_path('js/lib/field-limits.json')), true);

    expect(FieldLimits::get('product.name'))->toBe($json['product']['name'])
        ->and(FieldLimits::max('tag.name'))->toBe('max:'.$json['tag']['name']);
});

test('an unknown field limit throws', function () {
    FieldLimits::get('product.nope');
})->throws(InvalidArgumentException::class);

test('a product name at the limit is accepted and one character over is rejected', function () {
    $admin = adminUser(['products.create']);
    $limit = FieldLimits::get('product.name');

    $this->actingAs($admin)
        ->post(route('admin.products.store'), productPayload(['name' => str_repeat('a', $limit + 1)]))
        ->assertSessionHasErrors('name');

    $this->actingAs($admin)
        ->post(route('admin.products.store'), productPayload(['name' => str_repeat('a', $limit)]))
        ->assertSessionHasNoErrors();
});

test('textarea line breaks count as one character, matching the browser maxlength', function () {
    $admin = adminUser(['products.create']);
    $limit = FieldLimits::get('product.short_description');

    // What a browser submits for a textarea filled to maxlength with line breaks.
    $lines = intdiv($limit, 10);
    $text = implode("\r\n", array_fill(0, $lines, str_repeat('a', 9)));
    expect(mb_strlen(str_replace("\r\n", "\n", $text)))->toBeLessThanOrEqual($limit)
        ->and(mb_strlen($text))->toBeGreaterThan($limit);

    $this->actingAs($admin)
        ->post(route('admin.products.store'), productPayload(['short_description' => $text]))
        ->assertSessionHasNoErrors();

    expect(Product::query()->firstOrFail()->short_description)->not->toContain("\r");
});

test('prices and stock above the numeric maximum are rejected', function () {
    $admin = adminUser(['products.create']);

    $this->actingAs($admin)
        ->post(route('admin.products.store'), productPayload([
            'regular_price' => FieldLimits::get('number.price') + 1,
            'stock_quantity' => FieldLimits::get('number.stock_quantity') + 1,
        ]))
        ->assertSessionHasErrors(['regular_price', 'stock_quantity']);
});
