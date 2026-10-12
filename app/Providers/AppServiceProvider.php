<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

        // `composer run dev` also runs the scheduler, so SLA alerts and auto-close work locally.
        DevCommands::artisan('schedule:work', 'scheduler');

        // 60 API requests a minute per signed-in user (or per IP before signing in).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Token requests are limited per email and IP, like the sign-in form.
        RateLimiter::for('api-token', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
