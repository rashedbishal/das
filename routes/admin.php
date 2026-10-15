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
    Route::delete('products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');

    Route::resource('homepage-sections', HomepageSectionController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('settings/auth', [SettingsController::class, 'updateAuth'])->name('settings.auth.update');
    Route::put('settings/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
    Route::put('settings/messenger', [SettingsController::class, 'updateMessenger'])->name('settings.messenger.update');
    Route::put('settings/catalog-seo', [SettingsController::class, 'updateCatalogSeo'])->name('settings.catalog-seo.update');

    Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
});
