<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;

test('the catalogue page renders its first page of published products server-side', function () {
    $published = Product::factory()->published()->create(['name' => 'Visible Crucible']);
    Product::factory()->create(['name' => 'Draft Crucible']);

    $response = $this->get(route('catalogue'));

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('storefront/Catalogue')
        ->has('initialProducts.data', 1)
        ->where('initialProducts.data.0.slug', $published->slug)
        ->where('initialProducts.meta.total', 1));
});

test('the catalogue page applies url filters to the server-rendered products', function () {
    $chair = Product::factory()->published()->create(['name' => 'Dental Chair Pro']);
    Product::factory()->published()->create(['name' => 'Polishing Brush']);

    $this->get(route('catalogue', ['q' => 'chair']))
        ->assertInertia(fn ($page) => $page
            ->has('initialProducts.data', 1)
            ->where('initialProducts.data.0.slug', $chair->slug));
});

test('a category page renders the category and its products server-side', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $subcategory = Subcategory::factory()->for($category)->create(['is_active' => true]);
    $inCategory = Product::factory()->published()->for($category)->create();
    Product::factory()->published()->create();

    $this->get(route('categories.show', $category->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Category')
            ->where('category.slug', $category->slug)
            ->where('category.products_count', 1)
            ->has('category.subcategories', 1)
            ->where('category.subcategories.0.slug', $subcategory->slug)
            ->has('initialProducts.data', 1)
            ->where('initialProducts.data.0.slug', $inCategory->slug));
});

test('unknown or inactive category pages return a real 404', function (callable $slug) {
    $this->get(route('categories.show', $slug()))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('storefront/NotFound'));
})->with([
    'unknown slug' => [fn () => 'no-such-category'],
    'inactive category' => [fn () => Category::factory()->create(['is_active' => false])->slug],
]);

test('the sitemap lists public pages and active categories but not products', function () {
    $active = Category::factory()->create(['is_active' => true]);
    $inactive = Category::factory()->create(['is_active' => false]);
    $product = Product::factory()->published()->create();

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('catalogue'), escape: false)
        ->assertSee(route('categories.show', $active->slug), escape: false)
        ->assertDontSee(route('categories.show', $inactive->slug), escape: false)
        ->assertDontSee($product->slug, escape: false);
});

test('robots.txt points crawlers at the sitemap and away from the admin area', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'), escape: false)
        ->assertSee('Disallow: /admin', escape: false);
});
