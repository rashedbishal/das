<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicSettingsController;
use App\Http\Controllers\StorefrontHomeController;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', StorefrontHomeController::class)->name('home');
Route::inertia('catalogue', 'storefront/Catalogue')->name('catalogue');
Route::inertia('categories', 'storefront/Categories')->name('categories.index');
Route::inertia('categories/{category}', 'storefront/Category')->name('categories.show');

// Not a plain Route::inertia(): guests need the page to stash an intended URL so the
// inline login modal (see storefront/Product.svelte) returns them here after login,
// the same way Catalog\ProductController::show() does for the real catalog.
Route::get('products/{slug}', function (Request $request, SettingsService $settings) {
    if (! $request->user()) {
        $request->session()->put('url.intended', $request->fullUrl());
    }

    return Inertia::render('storefront/Product', [
        'loginMode' => $settings->loginMode(),
    ]);
})->name('products.show');

Route::inertia('brands', 'storefront/Brands')->name('brands');
Route::inertia('about', 'storefront/About')->name('about');
Route::inertia('contact', 'storefront/Contact')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::get('contact-widgets', [PublicSettingsController::class, 'widgets'])->name('contact-widgets');

// Prefixed so the real catalog doesn't collide with the storefront demo's /products and /categories URLs.
Route::prefix('catalog')->name('catalog.')->group(function () {
    Route::get('/', HomeController::class)->name('home');
    require __DIR__.'/catalog.php';
});

require __DIR__.'/admin.php';

Route::fallback(fn () => Inertia::render('storefront/NotFound')->toResponse(request())->setStatusCode(404));
