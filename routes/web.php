<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StyleguideController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}

// Unknown URLs go through the web middleware too, so signed-in users get the 404 page inside the app.
Route::fallback(fn () => abort(404));
