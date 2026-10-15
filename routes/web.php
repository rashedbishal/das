<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('contact-widgets', [PublicSettingsController::class, 'widgets'])->name('contact-widgets');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/catalog.php';
require __DIR__.'/admin.php';
