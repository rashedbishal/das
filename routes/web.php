<?php

use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Http\Controllers\Auth\GoogleSignInController;
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

// Product details are for signed-in visitors only; guests are sent to sign in and brought back.
Route::get('products/{slug}', StorefrontProductController::class)->middleware(['auth', 'verified'])->name('products.show');

Route::get('brands', [StorefrontCatalogueController::class, 'brands'])->name('brands');
Route::get('brands/{brand}', [StorefrontCatalogueController::class, 'brand'])->name('brands.show');
Route::inertia('about', 'storefront/About')->name('about');
Route::inertia('contact', 'storefront/Contact')->name('contact');
Route::inertia('privacy-policy', 'storefront/PrivacyPolicy')->name('privacy');
Route::inertia('terms-and-conditions', 'storefront/Terms')->name('terms');

// 6-digit e-mail verification code (sent on registration; "resend" uses Fortify's
// verification.send route, which calls User::sendEmailVerificationNotification()).
Route::post('email/verify-code', EmailVerificationCodeController::class)
    ->middleware(['auth', 'throttle:10,1'])
    ->name('verification.code');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::get('contact-widgets', [PublicSettingsController::class, 'widgets'])->name('contact-widgets');
Route::post('contact/enquiries', [ContactEnquirySubmissionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.enquiries.store');

// Customer "Continue with Google" (admins keep using /admin/login).
Route::middleware(['guest', 'throttle:10,1'])->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [GoogleSignInController::class, 'redirect'])->name('redirect');
    Route::get('callback', [GoogleSignInController::class, 'callback'])->name('callback');
});

require __DIR__.'/admin.php';

Route::fallback(fn () => Inertia::render('storefront/NotFound')->toResponse(request())->setStatusCode(404));
