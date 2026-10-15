<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicSettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'storefront/Home')->name('home');
Route::inertia('catalogue', 'storefront/Catalogue')->name('catalogue');
Route::inertia('categories', 'storefront/Categories')->name('categories.index');
Route::inertia('categories/{category}', 'storefront/Category')->name('categories.show');
Route::inertia('products/{slug}', 'storefront/Product')->name('products.show');
Route::inertia('brands', 'storefront/Brands')->name('brands');
Route::inertia('about', 'storefront/About')->name('about');
Route::inertia('contact', 'storefront/Contact')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::get('contact-widgets', [PublicSettingsController::class, 'widgets'])->name('contact-widgets');

// Prefixed so the real catalog doesn't collide with the storefront demo's /products and /categories URLs.
Route::prefix('catalog')->name('catalog.')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    require __DIR__.'/catalog.php';
});

require __DIR__.'/admin.php';

Route::fallback(fn () => Inertia::render('storefront/NotFound')->toResponse(request())->setStatusCode(404));
