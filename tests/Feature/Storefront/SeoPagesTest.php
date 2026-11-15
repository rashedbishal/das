<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\SettingsService;

test('the category and brand directories are rendered server-side with every active entry', function () {
    $category = Category::factory()->create(['is_active' => true, 'name' => 'Instruments']);
    Category::factory()->create(['is_active' => false]);
    $brand = Brand::factory()->create(['is_active' => true, 'name' => 'Shofu']);
    Brand::factory()->create(['is_active' => false]);

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Categories')
            ->has('categories', 1)
            ->where('categories.0.slug', $category->slug));

    $this->get(route('brands'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Brands')
            ->has('brands', 1)
            ->where('brands.0.slug', $brand->slug));
});

test('a brand page shows the brand, its SEO text and only its products', function () {
    $brand = Brand::factory()->create(['is_active' => true, 'seo_title' => 'Shofu dental products', 'seo_description' => 'All Shofu products.']);
    $own = Product::factory()->published()->for($brand)->create();
    Product::factory()->published()->create();

    $this->get(route('brands.show', $brand->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Brand')
            ->where('brand.slug', $brand->slug)
            ->where('brand.seo.title', 'Shofu dental products')
            ->where('brand.products_count', 1)
            ->has('initialProducts.data', 1)
            ->where('initialProducts.data.0.slug', $own->slug));
});

test('a subcategory page shows its own products within its category', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $subcategory = Subcategory::factory()->for($category)->create(['is_active' => true, 'seo_description' => 'Porcelain materials.']);
    $inside = Product::factory()->published()->for($category)->create(['subcategory_id' => $subcategory->id]);
    Product::factory()->published()->for($category)->create();

    $this->get(route('categories.subcategory', [$category->slug, $subcategory->slug]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/Subcategory')
            ->where('category.slug', $category->slug)
            ->where('subcategory.slug', $subcategory->slug)
            ->where('subcategory.seo.description', 'Porcelain materials.')
            ->has('initialProducts.data', 1)
            ->where('initialProducts.data.0.slug', $inside->slug));
});

test('unknown, inactive or mismatched brand and subcategory pages return a real 404', function (callable $url) {
    $this->get($url())->assertNotFound()->assertInertia(fn ($page) => $page->component('storefront/NotFound'));
})->with([
    'unknown brand' => [fn () => route('brands.show', 'no-such-brand')],
    'inactive brand' => [fn () => route('brands.show', Brand::factory()->create(['is_active' => false])->slug)],
    'inactive subcategory' => [function () {
        $category = Category::factory()->create(['is_active' => true]);

        return route('categories.subcategory', [$category->slug, Subcategory::factory()->for($category)->create(['is_active' => false])->slug]);
    }],
    'subcategory of another category' => [function () {
        $category = Category::factory()->create(['is_active' => true]);
        $other = Subcategory::factory()->for(Category::factory()->create(['is_active' => true]))->create(['is_active' => true]);

        return route('categories.subcategory', [$category->slug, $other->slug]);
    }],
]);

test('private and account pages tell search engines not to index them; storefront pages do not', function () {
    $this->get(route('login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get(route('register'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get(route('admin.login'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->get(route('home'))->assertHeaderMissing('X-Robots-Tag');
    $this->get(route('catalogue'))->assertHeaderMissing('X-Robots-Tag');
});

test('storefront pages share category links for the server-rendered footer', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $this->get(route('about'))->assertInertia(fn ($page) => $page->where('navCategories.0.slug', $category->slug));
});

test('CMS pages do not load the storefront category links', function () {
    Category::factory()->create(['is_active' => true]);

    $this->actingAs(adminUser(['tags.view']))
        ->get(route('admin.tags.index'))
        ->assertInertia(fn ($page) => $page->missing('navCategories'));
});

test('business location, opening hours and social links are saved and shared with the storefront', function () {
    $admin = adminUser(['settings.manage']);
    $hours = collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
        ->map(fn (string $day) => $day === 'friday'
            ? ['day' => $day, 'closed' => '1']
            : ['day' => $day, 'closed' => '0', 'opens' => '10:00', 'closes' => '19:00'])
        ->all();

    $this->actingAs($admin)->put(route('admin.settings.general.update'), [
        'site_name' => 'DAS',
        'currency' => 'BDT',
        'timezone' => 'Asia/Dhaka',
        'address' => 'House 12, Road 5, Dhanmondi',
        'address_city' => 'Dhaka',
        'postal_code' => '1205',
        'map_url' => 'https://maps.app.goo.gl/example',
        'latitude' => '23.7461',
        'longitude' => '90.3742',
        'opening_hours' => $hours,
        'social_links' => ['facebook' => 'https://facebook.com/das'],
    ])->assertSessionHasNoErrors();

    $this->get(route('contact'))->assertInertia(fn ($page) => $page
        ->where('siteSettings.addressCity', 'Dhaka')
        ->where('siteSettings.mapUrl', 'https://maps.app.goo.gl/example')
        ->where('siteSettings.openingHours.0.opens', '10:00')
        ->where('siteSettings.openingHours.6.closed', true)
        ->where('siteSettings.socialLinks.facebook', 'https://facebook.com/das'));
});

test('opening hours must close after they open', function () {
    $hours = collect(['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])
        ->map(fn (string $day) => ['day' => $day, 'closed' => '0', 'opens' => '18:00', 'closes' => '09:00'])
        ->all();

    $this->actingAs(adminUser(['settings.manage']))->put(route('admin.settings.general.update'), [
        'site_name' => 'DAS',
        'currency' => 'BDT',
        'timezone' => 'Asia/Dhaka',
        'opening_hours' => $hours,
    ])->assertSessionHasErrors('opening_hours.0.closes');
});

test('a pasted Search Console tag is reduced to its code and shared with the storefront', function () {
    $this->actingAs(adminUser(['settings.manage']))->put(route('admin.settings.catalog-seo.update'), [
        'page_size' => 12,
        'featured_limit' => 8,
        'google_site_verification' => '<meta name="google-site-verification" content="abc123_XYZ-9" />',
        'bing_site_verification' => '',
    ])->assertSessionHasNoErrors();

    expect(app(SettingsService::class)->get('seo', 'google_site_verification'))->toBe('abc123_XYZ-9');

    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('seo.googleSiteVerification', 'abc123_XYZ-9'));
});

test('category, subcategory and brand SEO fields are saved from the CMS', function () {
    $admin = adminUser(['categories.manage', 'brands.manage']);
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'description' => 'Hand instruments for every procedure.',
        'seo_title' => 'Dental hand instruments',
        'seo_description' => 'Shop dental hand instruments in Bangladesh.',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->put(route('admin.brands.update', $brand), [
        'name' => $brand->name,
        'seo_title' => str_repeat('a', 71),
        'is_active' => '1',
    ])->assertSessionHasErrors('seo_title');

    expect($category->fresh())
        ->seo_title->toBe('Dental hand instruments')
        ->description->toBe('Hand instruments for every procedure.');
});

test('the SEO health page lists pages with content gaps and site settings still to do', function () {
    $thin = Product::factory()->published()->create(['name' => 'Thin Product', 'short_description' => null, 'full_description' => null]);
    Product::factory()->create(['status' => 'draft', 'full_description' => null]);

    $this->actingAs(adminUser(['seo.manage']))
        ->get(route('admin.seo.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/seo/Index')
            ->where('report.items', fn ($items) => collect($items)->where('type', 'product')->pluck('url')->all() === [route('products.show', $thin->slug)])
            ->where('report.site.0.label', 'Google Search Console connected')
            ->where('report.site.0.ok', false));
});

test('the SEO health page needs seo.manage or products.view', function () {
    $this->actingAs(adminUser(['tags.view']))->get(route('admin.seo.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.seo.index'))->assertForbidden();
});

test('the home page renders its featured products server-side, without prices for guests', function () {
    $featured = Product::factory()->published()->featured()->create(['regular_price' => 900, 'show_price' => true]);
    Product::factory()->published()->create();
    Product::factory()->featured()->create(['status' => 'draft']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('featuredProducts', 1)
            ->where('featuredProducts.0.slug', $featured->slug)
            ->where('featuredProducts.0.regular_price', null));
});
