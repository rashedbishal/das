<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\SettingsController;
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
    Route::resource('categories', CategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('subcategories', SubcategoryController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('brands', BrandController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('tags', TagController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('products', ProductController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::post('products/{product}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::patch('products/{product}/media/{media}', [ProductMediaController::class, 'update'])->name('products.media.update');
    Route::delete('products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');

    Route::resource('homepage-sections', HomepageSectionController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('settings/auth', [SettingsController::class, 'updateAuth'])->name('settings.auth.update');
    Route::put('settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
    Route::put('settings/messenger', [SettingsController::class, 'updateMessenger'])->name('settings.messenger.update');
    Route::put('settings/catalog-seo', [SettingsController::class, 'updateCatalogSeo'])->name('settings.catalog-seo.update');
    Route::put('settings/tracking', [SettingsController::class, 'updateTracking'])->name('settings.tracking.update');

    Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
});
