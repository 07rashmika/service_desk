<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TicketCommentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketWorkflowController;
use App\Http\Controllers\TicketAttachmentController;
use Illuminate\Support\Facades\Route;

/*
| ServiceDesk REST API, version 1. Every route except getting a token needs
| "Authorization: Bearer <token>". Permissions are the same as on the website.
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/token', [AuthController::class, 'store'])->middleware('throttle:api-token')->name('auth.token');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::delete('/auth/token', [AuthController::class, 'destroy'])->name('auth.revoke');

        Route::get('/lookups', LookupController::class)->name('lookups');

        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [TicketWorkflowController::class, 'triage'])->name('tickets.triage');
        Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');

        Route::post('/tickets/{ticket}/take', [TicketWorkflowController::class, 'take'])->name('tickets.take');
        Route::post('/tickets/{ticket}/assign', [TicketWorkflowController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/start', [TicketWorkflowController::class, 'start'])->name('tickets.start');
        Route::post('/tickets/{ticket}/ask', [TicketWorkflowController::class, 'ask'])->name('tickets.ask');
        Route::post('/tickets/{ticket}/resolve', [TicketWorkflowController::class, 'resolve'])->name('tickets.resolve');
        Route::post('/tickets/{ticket}/close', [TicketWorkflowController::class, 'close'])->name('tickets.close');
        Route::post('/tickets/{ticket}/reopen', [TicketWorkflowController::class, 'reopen'])->name('tickets.reopen');

        Route::get('/attachments/{attachment}', [TicketAttachmentController::class, 'show'])->name('attachments.show');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });
});
