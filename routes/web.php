<?php

use App\Enums\PermissionName;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\TicketCategoryController;
use App\Http\Controllers\Admin\TicketPriorityController;
use App\Http\Controllers\Admin\TicketStatusController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\Support\SupportTicketActionController;
use App\Http\Controllers\Support\SupportTicketController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketResolutionController;
use App\Models\Ticket;
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

    Route::prefix('support')->name('support.')->middleware('can:viewQueue,'.Ticket::class)->group(function () {
        Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/mine', [SupportTicketController::class, 'assigned'])->name('tickets.assigned');
        Route::post('/tickets/bulk', [SupportTicketActionController::class, 'bulk'])->name('tickets.bulk');
        Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/take', [SupportTicketActionController::class, 'take'])->name('tickets.take');
        Route::post('/tickets/{ticket}/assign', [SupportTicketActionController::class, 'assign'])->name('tickets.assign');
        Route::post('/tickets/{ticket}/start', [SupportTicketActionController::class, 'start'])->name('tickets.start');
        Route::post('/tickets/{ticket}/ask', [SupportTicketActionController::class, 'askRequester'])->name('tickets.ask');
        Route::post('/tickets/{ticket}/resolve', [SupportTicketActionController::class, 'resolve'])->name('tickets.resolve');
        Route::patch('/tickets/{ticket}', [SupportTicketActionController::class, 'triage'])->name('tickets.triage');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('can:'.PermissionName::ManageUsers->value)->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
            Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
            Route::post('/users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])->name('users.password-reset');
            Route::get('/technicians', [TechnicianController::class, 'index'])->name('technicians.index');
        });

        Route::middleware('can:'.PermissionName::ManageSettings->value)->group(function () {
            Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('categories', TicketCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('/categories/{category}/toggle', [TicketCategoryController::class, 'toggle'])->name('categories.toggle');
            Route::resource('priorities', TicketPriorityController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('statuses', TicketStatusController::class)->only(['index', 'update']);
        });

        Route::middleware('can:'.PermissionName::ManageRoles->value)->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
    });
});

if (app()->environment(['local', 'testing'])) {
    Route::get('/styleguide', StyleguideController::class)->name('styleguide');
}

// Unknown URLs go through the web middleware too, so signed-in users get the 404 page inside the app.
Route::fallback(fn () => abort(404));
