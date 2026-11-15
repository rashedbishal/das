<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\FieldLimits;

test('the stock page lists products newest first with their main image, excluding trashed ones', function () {
    $admin = adminUser(['products.view']);
    $older = Product::factory()->create(['created_at' => now()->subDays(2)]);
    $newer = Product::factory()->create(['created_at' => now()->subDay()]);
    Product::factory()->create()->delete();
    $image = ProductMedia::factory()->for($newer)->create(['is_main' => true, 'path' => 'products/main.webp']);

    $this->actingAs($admin)
        ->get(route('admin.stock.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/stock/Index')
            ->where('products.total', 2)
            ->where('products.data.0.id', $newer->id)
            ->where('products.data.0.image_url', $image->resolvedUrl())
            ->where('products.data.1.id', $older->id)
            ->where('products.data.1.image_url', null)
            ->where('canAdjust', false));
});

test('the stock page search matches name or SKU and treats wildcards literally', function () {
    $admin = adminUser(['products.view']);
    $chair = Product::factory()->create(['name' => 'Dental Chair', 'sku' => 'CH-1']);
    $scaler = Product::factory()->create(['name' => 'Ultrasonic Scaler', 'sku' => 'SC_100']);

    $this->actingAs($admin)
        ->get(route('admin.stock.index', ['search' => 'chair']))
        ->assertInertia(fn ($page) => $page
            ->where('products.total', 1)
            ->where('products.data.0.id', $chair->id)
            ->where('filters.search', 'chair'));

    $this->actingAs($admin)
        ->get(route('admin.stock.index', ['search' => 'SC_']))
        ->assertInertia(fn ($page) => $page->where('products.total', 1)->where('products.data.0.id', $scaler->id));

    $this->actingAs($admin)
        ->get(route('admin.stock.index', ['search' => '%']))
        ->assertInertia(fn ($page) => $page->where('products.total', 0));
});

test('the stock page requires products.view', function () {
    $this->actingAs(adminUser())->get(route('admin.stock.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.stock.index'))->assertForbidden();
});

test('adding stock increases the quantity, records the movement and marks an out-of-stock product in stock', function () {
    $admin = adminUser(['products.view', 'products.update']);
    $product = Product::factory()->outOfStock()->create();

    $this->actingAs($admin)
        ->from(route('admin.stock.index'))
        ->post(route('admin.stock.store', $product), ['type' => 'add', 'quantity' => 25, 'note' => 'Shipment received'])
        ->assertRedirect(route('admin.stock.index'))
        ->assertSessionHasNoErrors();

    $product->refresh();
    expect($product->stock_quantity)->toBe(25)
        ->and($product->stock_status)->toBe('in_stock')
        ->and($product->updated_by)->toBe($admin->id);

    $movement = StockMovement::query()->sole();
    expect($movement->product_id)->toBe($product->id)
        ->and($movement->user_id)->toBe($admin->id)
        ->and($movement->type)->toBe('add')
        ->and($movement->quantity)->toBe(25)
        ->and($movement->quantity_before)->toBe(0)
        ->and($movement->quantity_after)->toBe(25)
        ->and($movement->note)->toBe('Shipment received');

    $this->assertDatabaseHas('activity_log', ['event' => 'stock_added', 'subject_id' => $product->id, 'causer_id' => $admin->id]);
    $this->assertDatabaseMissing('activity_log', ['event' => 'updated', 'subject_id' => $product->id]);
});

test('removing all stock marks an in-stock product out of stock', function () {
    $admin = adminUser(['products.update']);
    $product = Product::factory()->create(['stock_quantity' => 5, 'stock_status' => 'in_stock']);

    $this->actingAs($admin)
        ->post(route('admin.stock.store', $product), ['type' => 'remove', 'quantity' => 5])
        ->assertSessionHasNoErrors();

    $product->refresh();
    expect($product->stock_quantity)->toBe(0)
        ->and($product->stock_status)->toBe('out_of_stock');
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id, 'type' => 'remove', 'quantity' => 5, 'quantity_before' => 5, 'quantity_after' => 0,
    ]);
});

test('backorder and on-order statuses are left alone by stock changes', function (string $status, string $type, int $before, int $quantity) {
    $product = Product::factory()->create(['stock_quantity' => $before, 'stock_status' => $status]);

    $this->actingAs(adminUser(['products.update']))
        ->post(route('admin.stock.store', $product), ['type' => $type, 'quantity' => $quantity])
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock_status)->toBe($status);
})->with([
    'backorder emptied' => ['backorder', 'remove', 3, 3],
    'on order restocked' => ['on_order', 'add', 0, 10],
]);

test('removing more than is in stock is rejected and changes nothing', function () {
    $product = Product::factory()->create(['stock_quantity' => 3, 'stock_status' => 'in_stock']);

    $this->actingAs(adminUser(['products.update']))
        ->post(route('admin.stock.store', $product), ['type' => 'remove', 'quantity' => 4])
        ->assertSessionHasErrors('quantity');

    expect($product->fresh()->stock_quantity)->toBe(3);
    expect(StockMovement::query()->count())->toBe(0);
});

test('adding stock past the maximum is rejected', function () {
    $maximum = FieldLimits::get('number.stock_quantity');
    $product = Product::factory()->create(['stock_quantity' => $maximum]);

    $this->actingAs(adminUser(['products.update']))
        ->post(route('admin.stock.store', $product), ['type' => 'add', 'quantity' => 1])
        ->assertSessionHasErrors('quantity');

    expect($product->fresh()->stock_quantity)->toBe($maximum);
});

test('a stock change needs a valid type, a positive whole quantity and a short note', function (array $payload, string $field) {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->actingAs(adminUser(['products.update']))
        ->post(route('admin.stock.store', $product), [...['type' => 'add', 'quantity' => 1], ...$payload])
        ->assertSessionHasErrors($field);

    expect($product->fresh()->stock_quantity)->toBe(10);
})->with([
    'unknown type' => [['type' => 'set'], 'type'],
    'zero quantity' => [['quantity' => 0], 'quantity'],
    'fractional quantity' => [['quantity' => 1.5], 'quantity'],
    'long note' => [['note' => str_repeat('a', 256)], 'note'],
]);

test('changing stock requires products.update', function () {
    $product = Product::factory()->create(['stock_quantity' => 10]);

    $this->actingAs(adminUser(['products.view']))
        ->post(route('admin.stock.store', $product), ['type' => 'add', 'quantity' => 1])
        ->assertForbidden();

    expect($product->fresh()->stock_quantity)->toBe(10);

    $this->actingAs(adminUser(['products.view', 'products.update']))
        ->get(route('admin.stock.index'))
        ->assertInertia(fn ($page) => $page->where('canAdjust', true));
});

test('the history endpoint returns a product\'s latest stock changes, newest first', function () {
    $admin = adminUser(['products.view']);
    $product = Product::factory()->create();
    StockMovement::factory()->for($product)->create(['created_at' => now()->subHour(), 'quantity' => 4]);
    $latest = StockMovement::factory()->for($product)->for($admin)->create(['type' => 'remove', 'quantity' => 2, 'note' => 'Sold']);
    StockMovement::factory()->create();

    $this->actingAs($admin)
        ->getJson(route('admin.stock.history', $product))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $latest->id)
        ->assertJsonPath('data.0.type', 'remove')
        ->assertJsonPath('data.0.note', 'Sold')
        ->assertJsonPath('data.0.user', $admin->name)
        ->assertJsonPath('data.1.quantity', 4);

    $this->actingAs(adminUser())->getJson(route('admin.stock.history', $product))->assertForbidden();
});

test('the product form no longer needs a stock quantity and editing keeps the current stock', function () {
    $admin = adminUser(['products.create', 'products.update']);
    $category = Category::factory()->create();

    $this->actingAs($admin)->post(route('admin.products.store'), [
        'name' => 'Curing Light',
        'sku' => 'SKU-CURE',
        'status' => 'draft',
        'stock_status' => 'in_stock',
        'category_id' => $category->id,
    ])->assertSessionHasNoErrors();

    $product = Product::query()->where('sku', 'SKU-CURE')->sole();
    expect($product->stock_quantity)->toBe(0);

    $product->update(['stock_quantity' => 12]);

    $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => 'Curing Light LED',
        'sku' => 'SKU-CURE',
        'status' => 'draft',
        'stock_status' => 'in_stock',
        'category_id' => $category->id,
    ])->assertSessionHasNoErrors();

    expect($product->fresh()->stock_quantity)->toBe(12)
        ->and($product->fresh()->name)->toBe('Curing Light LED');
});
