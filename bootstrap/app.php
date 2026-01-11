<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckQuota;

return Application::configure(basePath: dirname(__DIR__))

    /*
    |--------------------------------------------------------------------------
    | ROUTING
    |--------------------------------------------------------------------------
    */
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | MIDDLEWARE CONFIG
    |--------------------------------------------------------------------------
    */
    ->withMiddleware(function (Middleware $middleware): void {

        /**
         * ==================================================
         * GLOBAL WEB MIDDLEWARE STACK
         * ==================================================
         * Urutan penting:
         * - Inertia dulu
         * - Asset preload
         * - Baru behavior log
         */
        $middleware->web(append: [

            // Inertia
            \App\Http\Middleware\HandleInertiaRequests::class,

            // Laravel default
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,

            /**
             * ==================================================
             * USER BEHAVIOR & SYSTEM LOGGING
             * ==================================================
             * Aman:
             * - read-only
             * - no side effect
             * - failure silent
             */
            \App\Http\Middleware\LogUserBehavior::class,
        ]);

        /**
         * ==================================================
         * MIDDLEWARE ALIAS
         * ==================================================
         */
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | SCHEDULED TASKS (Laravel 12)
    |--------------------------------------------------------------------------
    */
    ->withSchedule(function ($schedule) {

        // contoh existing scheduler
        $schedule->command('waba:sync-templates')->hourly();

        // (kalau ada SLA, scheduler lain masuk sini)
        // $schedule->call(fn () => TicketSlaService::run())->everyMinute();
    })

    /*
    |--------------------------------------------------------------------------
    | EXCEPTION HANDLER
    |--------------------------------------------------------------------------
    */
    ->withExceptions(function (Exceptions $exceptions): void {
        // default, biarin kosong
    })

    ->create();

    $app->routeMiddleware([
    'quota' => CheckQuota::class,

    withMiddleware(function ($middleware) {
    $middleware->alias([
        'verified_or_grace' => \App\Http\Middleware\EnsureVerifiedOrInGracePeriod::class,
    ]);
})
]);
