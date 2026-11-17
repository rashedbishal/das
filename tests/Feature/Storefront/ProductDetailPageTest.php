<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductSpecification;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;

test('a guest is sent to sign in instead of seeing the product page', function () {
    $product = Product::factory()->published()->create(['full_description' => 'A cordless LED curing light.']);

    $this->get(route('products.show', $product->slug))
        ->assertRedirect(route('login'));

    $this->get(route('products.show', 'no-such-product'))
        ->assertRedirect(route('login'));
});

test('a signed-in customer sees the full product page, server-rendered', function () {
    $product = Product::factory()->published()->create([
        'name' => 'Curing Light X',
        'full_description' => 'A cordless LED curing light.',
        'brand_id' => Brand::factory(),
        'category_id' => Category::factory(),
    ]);
    ProductSpecification::factory()->for($product)->create(['name' => 'Wavelength', 'value' => '420-480 nm']);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Product')
            ->where('isPreview', false)
            ->where('product.name', 'Curing Light X')
            ->where('product.full_description', 'A cordless LED curing light.')
            ->where('product.specifications.0.value', '420-480 nm')
            ->where('product.brand.slug', $product->brand->slug));
});

test('a signed-in customer sees the price on the product page', function () {
    $product = Product::factory()->published()->create(['regular_price' => 4500, 'show_price' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('product.regular_price', '4500.00')
            ->where('product.price_login_required', false));
});

test('unknown, draft and archived products return a real 404', function (?string $status) {
    $slug = $status === null ? 'no-such-product' : Product::factory()->create(['status' => $status])->slug;

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $slug))
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('storefront/NotFound'));
})->with(['unknown' => [null], 'draft' => ['draft'], 'archived' => ['archived']]);

test('an admin can preview a draft product, marked as a preview', function () {
    $product = Product::factory()->create(['status' => 'draft']);

    $this->actingAs(User::factory()->admin(Role::factory()->create())->create())
        ->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('isPreview', true));
});

test('a customer returns to the requested product URL after signing in', function () {
    $customer = User::factory()->create(['password' => 'password']);
    $product = Product::factory()->published()->create();

    $this->get(route('products.show', $product->slug));

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('products.show', $product->slug, absolute: false));
});

test('guests see no prices on product cards and cannot filter or sort by price', function () {
    $cheap = Product::factory()->published()->create(['regular_price' => 100, 'show_price' => true, 'name' => 'B cheap']);
    Product::factory()->published()->create(['regular_price' => 9000, 'show_price' => true, 'name' => 'A dear']);

    $this->getJson(route('api.v1.products.index', ['max_price' => 500, 'sort' => 'price_desc']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.regular_price', null)
        ->assertJsonPath('data.0.price_login_required', true);

    $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.products.index', ['max_price' => 500]))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $cheap->id)
        ->assertJsonPath('data.0.regular_price', '100.00');
});

test('related products reach the product page as a plain list of published products', function () {
    $product = Product::factory()->published()->create();
    $related = Product::factory()->published()->create();
    $draft = Product::factory()->create(['status' => 'draft']);
    $product->relatedProducts()->attach([$related->id, $draft->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->has('product.related_products', 1)
            ->where('product.related_products.0.slug', $related->slug));
});

test('related products are suggested automatically: picked ones, then shared tags, then the same category', function () {
    $category = Category::factory()->create();
    [$tagA, $tagB] = Tag::factory()->count(2)->create();
    $product = Product::factory()->published()->for($category)->create();
    $product->tags()->attach([$tagA->id, $tagB->id]);

    $picked = Product::factory()->published()->create();
    $product->relatedProducts()->attach($picked->id);
    $bothTags = Product::factory()->published()->create();
    $bothTags->tags()->attach([$tagA->id, $tagB->id]);
    $oneTag = Product::factory()->published()->create();
    $oneTag->tags()->attach($tagA->id);
    $sameCategory = Product::factory()->published()->for($category)->create();
    $draftWithTag = Product::factory()->create(['status' => 'draft']);
    $draftWithTag->tags()->attach($tagA->id);
    Product::factory()->published()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page->where(
            'product.related_products',
            fn ($related) => collect($related)->pluck('slug')->all() === [$picked->slug, $bothTags->slug, $oneTag->slug, $sameCategory->slug],
        ));
});

test('related product suggestions are capped at 8', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->published()->for($category)->create();
    Product::factory()->count(10)->published()->for($category)->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.products.show', $product))
        ->assertOk()
        ->assertJsonCount(8, 'data.related_products');
});

test('a product drafted or archived while a customer is on its page is a 404 on their next request', function (string $status) {
    $product = Product::factory()->published()->create();
    $this->actingAs(User::factory()->create());

    $this->get(route('products.show', $product->slug))->assertOk();

    $product->update(['status' => $status]);

    // A full reload, and an Inertia (client-side) visit, both see the change.
    $this->get(route('products.show', $product->slug))->assertNotFound();
    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())])
        ->get(route('products.show', $product->slug))
        ->assertNotFound();
})->with([
    'drafted' => ['draft'],
    'archived' => ['archived'],
]);

test('the product page receives the CMS SEO title, description and keywords', function () {
    $product = Product::factory()->published()->create([
        'seo_title' => 'Cordless Curing Light',
        'seo_description' => 'Buy a cordless LED curing light in Bangladesh.',
        'seo_keywords' => 'curing light, dental LED',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Product')
            ->where('product.seo.title', 'Cordless Curing Light')
            ->where('product.seo.description', 'Buy a cordless LED curing light in Bangladesh.')
            ->where('product.seo.keywords', 'curing light, dental LED'));
});

test('product images get alt text from the product name, numbered in gallery order', function () {
    $product = Product::factory()->published()->create(['name' => 'Curing Light X']);
    ProductMedia::factory()->for($product)->create(['sort_order' => 2, 'alt_text' => null]);
    ProductMedia::factory()->for($product)->create(['sort_order' => 1, 'alt_text' => null]);
    ProductMedia::factory()->for($product)->video()->create(['sort_order' => 3, 'alt_text' => null]);
    ProductMedia::factory()->for($product)->create(['sort_order' => 4, 'alt_text' => 'Curing light in its charging base']);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('product.media.0.alt', 'Curing Light X 01')
            ->where('product.media.1.alt', 'Curing Light X 02')
            ->where('product.media.2.alt', 'Curing Light X video')
            ->where('product.media.3.alt', 'Curing light in its charging base'));
});

test('a single product image uses the product name as its alt text', function () {
    $product = Product::factory()->published()->create(['name' => 'Curing Light X']);
    ProductMedia::factory()->for($product)->create(['alt_text' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page->where('product.media.0.alt', 'Curing Light X'));
});
