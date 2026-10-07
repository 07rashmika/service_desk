<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketResolutionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::post('/tickets/{ticket}/close', [TicketResolutionController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [TicketResolutionController::class, 'reopen'])->name('tickets.reopen');

    Route::get('/attachments/{attachment}', [TicketAttachmentController::class, 'show'])->name('attachments.show');
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}

// Unknown URLs go through the web middleware too, so signed-in users get the 404 page inside the app.
Route::fallback(fn () => abort(404));
