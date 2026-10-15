<?php

use App\Models\Product;
use App\Models\Tag;

test('an admin with tags.manage can create a tag', function () {
    $admin = adminUser(['tags.manage']);

    $response = $this->actingAs($admin)->post(route('admin.tags.store'), ['name' => 'Autoclavable']);

    $response->assertRedirect(route('admin.tags.index'));
    expect(Tag::query()->where('slug', 'autoclavable')->exists())->toBeTrue();
});

test('an admin without tags.manage cannot create a tag', function () {
    $admin = adminUser(['tags.view']);

    $response = $this->actingAs($admin)->post(route('admin.tags.store'), ['name' => 'Autoclavable']);

    $response->assertForbidden();
});

test('a tag cannot be deleted while assigned to a product', function () {
    $admin = adminUser(['tags.manage']);
    $tag = Tag::factory()->create();
    Product::factory()->create()->tags()->attach($tag);

    $response = $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag));

    $response->assertSessionHasErrors('tag');
});
