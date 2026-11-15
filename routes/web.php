<?php

use App\Http\Controllers\ContactEnquirySubmissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicSettingsController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StorefrontCatalogueController;
use App\Http\Controllers\StorefrontHomeController;
use App\Http\Controllers\StorefrontProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', StorefrontHomeController::class)->name('home');
Route::get('catalogue', [StorefrontCatalogueController::class, 'catalogue'])->name('catalogue');
Route::get('categories', [StorefrontCatalogueController::class, 'categories'])->name('categories.index');
Route::get('categories/{category}', [StorefrontCatalogueController::class, 'category'])->name('categories.show');
Route::get('categories/{category}/{subcategory}', [StorefrontCatalogueController::class, 'subcategory'])->name('categories.subcategory');

Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('products/{slug}', StorefrontProductController::class)->name('products.show');

Route::get('brands', [StorefrontCatalogueController::class, 'brands'])->name('brands');
Route::get('brands/{brand}', [StorefrontCatalogueController::class, 'brand'])->name('brands.show');
Route::inertia('about', 'storefront/About')->name('about');
Route::inertia('contact', 'storefront/Contact')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::get('contact-widgets', [PublicSettingsController::class, 'widgets'])->name('contact-widgets');
Route::post('contact/enquiries', [ContactEnquirySubmissionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.enquiries.store');

require __DIR__.'/admin.php';

Route::fallback(fn () => Inertia::render('storefront/NotFound')->toResponse(request())->setStatusCode(404));
