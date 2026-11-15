<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactEnquiryController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\SeoHealthController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// Admins sign in here rather than at the customer /login. The POST reuses Fortify's
// login pipeline (throttling, session regeneration); FortifyServiceProvider only
// accepts admin accounts on this route and LoginResponse sends them to the dashboard.
Route::middleware('guest')->prefix('admin')->name('admin.')->group(function () {
    Route::get('login', fn () => Inertia::render('auth/AdminLogin', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
    ]))->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:'.config('fortify.limiters.login'))
        ->name('login.store');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('enquiries/bulk', [ContactEnquiryController::class, 'bulk'])->name('enquiries.bulk');
    Route::resource('enquiries', ContactEnquiryController::class)
        ->only(['index', 'update', 'destroy']);

    Route::resource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('categories/bulk', [CategoryController::class, 'bulk'])->name('categories.bulk');
    Route::get('subcategories', [SubcategoryController::class, 'index'])->name('subcategories.index');
    Route::post('subcategories/bulk', [SubcategoryController::class, 'bulk'])->name('subcategories.bulk');

    Route::resource('subcategories', SubcategoryController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('brands', BrandController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('brands/bulk', [BrandController::class, 'bulk'])->name('brands.bulk');

    Route::resource('tags', TagController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('tags/bulk', [TagController::class, 'bulk'])->name('tags.bulk');

    Route::resource('products', ProductController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('products/bulk', [ProductController::class, 'bulk'])->name('products.bulk');
    Route::post('products/{product}/restore', [ProductController::class, 'restore'])
        ->withTrashed()
        ->name('products.restore');
    Route::delete('products/{product}/force', [ProductController::class, 'forceDestroy'])
        ->withTrashed()
        ->name('products.force-destroy');

    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('stock/{product}', [StockController::class, 'store'])->name('stock.store');
    Route::get('stock/{product}/history', [StockController::class, 'history'])->name('stock.history');

    Route::post('products/{product}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::patch('products/{product}/media/{media}', [ProductMediaController::class, 'update'])->name('products.media.update');
    Route::delete('products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');

    Route::resource('homepage-sections', HomepageSectionController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::post('homepage-sections/bulk', [HomepageSectionController::class, 'bulk'])->name('homepage-sections.bulk');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('settings/auth', [SettingsController::class, 'updateAuth'])->name('settings.auth.update');
    Route::put('settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
    Route::put('settings/messenger', [SettingsController::class, 'updateMessenger'])->name('settings.messenger.update');
    Route::put('settings/catalog-seo', [SettingsController::class, 'updateCatalogSeo'])->name('settings.catalog-seo.update');
    Route::put('settings/tracking', [SettingsController::class, 'updateTracking'])->name('settings.tracking.update');

    Route::get('users', [UserManagementController::class, 'index'])->name('users.index');

    Route::get('seo', SeoHealthController::class)->name('seo.index');

    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::delete('activity-log', [ActivityLogController::class, 'destroy'])->name('activity-log.destroy');
});
