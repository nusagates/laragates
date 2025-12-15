<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        // Vite prefetch (Laravel 12)
        Vite::prefetch(concurrency: 3);

        // Register console commands (ONLY when running in console)
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\WabaSyncTemplates::class,
            ]);
        }
    }
}
