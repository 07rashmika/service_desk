<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
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
    }
}
