<?php

use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ProductMediaController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Api\V1\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HomepageController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SubcategoryController;
use App\Http\Controllers\Api\V1\TagController;
use App\Http\Controllers\PublicSettingsController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('auth.register');
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('auth/user', [AuthController::class, 'user'])->name('auth.user');
});

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product:slug}', [ProductController::class, 'show'])->middleware('auth:sanctum')->name('products.show');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('subcategories/{subcategory:slug}', [SubcategoryController::class, 'show'])->name('subcategories.show');

Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('tags', [TagController::class, 'index'])->name('tags.index');

Route::get('homepage', HomepageController::class)->name('homepage');
Route::get('settings/contact-widgets', [PublicSettingsController::class, 'widgets'])->name('settings.contact-widgets');

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::apiResource('products', AdminProductController::class);
    Route::post('products/{product}/media', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::delete('products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');

    Route::apiResource('categories', AdminCategoryController::class)->except(['show']);
    Route::apiResource('subcategories', AdminSubcategoryController::class)->except(['show']);
    Route::apiResource('brands', AdminBrandController::class)->except(['show']);
    Route::apiResource('tags', AdminTagController::class)->except(['show']);

    Route::get('roles/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::apiResource('roles', RoleController::class);

    Route::apiResource('admins', AdminUserController::class);
    Route::post('admins/{admin}/deactivate', [AdminUserController::class, 'deactivate'])->name('admins.deactivate');
    Route::post('admins/{admin}/reactivate', [AdminUserController::class, 'reactivate'])->name('admins.reactivate');
    Route::post('admins/{admin}/transfer-mother-admin', [AdminUserController::class, 'transferMotherAdmin'])->name('admins.transfer-mother-admin');

    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('customers/{customer}/deactivate', [CustomerController::class, 'deactivate'])->name('customers.deactivate');
    Route::post('customers/{customer}/reactivate', [CustomerController::class, 'reactivate'])->name('customers.reactivate');

    Route::get('settings', [AdminSettingsController::class, 'show'])->name('settings.show');
    Route::put('settings/general', [AdminSettingsController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('settings/auth', [AdminSettingsController::class, 'updateAuth'])->name('settings.auth.update');
    Route::put('settings/whatsapp', [AdminSettingsController::class, 'updateWhatsapp'])->name('settings.whatsapp.update');
    Route::put('settings/messenger', [AdminSettingsController::class, 'updateMessenger'])->name('settings.messenger.update');
    Route::put('settings/catalog-seo', [AdminSettingsController::class, 'updateCatalogSeo'])->name('settings.catalog-seo.update');
});
