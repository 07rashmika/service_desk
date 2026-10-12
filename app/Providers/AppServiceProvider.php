<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Reports\TicketReport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Outside production, loading a relationship inside a loop throws instead of
        // silently running one query per row (the "N+1" problem).
        Model::preventLazyLoading(! $this->app->isProduction());

        // Reports are cached, so any change to tickets or the things they're grouped by makes them out of date.
        foreach ([Ticket::class, TicketStatus::class, TicketPriority::class, TicketCategory::class, Department::class, User::class] as $model) {
            $model::saved(fn () => TicketReport::flushCache());
            $model::deleted(fn () => TicketReport::flushCache());
        }

        // `composer run dev` also runs the scheduler, so SLA alerts and auto-close work locally.
        DevCommands::artisan('schedule:work', 'scheduler');

        // 60 API requests a minute per signed-in user (or per IP before signing in).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Token requests are limited per email and IP, like the sign-in form.
        RateLimiter::for('api-token', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        // New tickets, replies and invite emails: plenty for a person, but stops a stuck script or spam.
        RateLimiter::for('submissions', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

        // In production, also reject passwords that appear in known data breaches.
        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(8)->max(255)->uncompromised()
            : Password::min(8)->max(255));
    }
}
