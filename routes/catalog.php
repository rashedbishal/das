<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/{product:slug}', [ProductController::class, 'show'])->middleware('auth')->name('products.show');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');
