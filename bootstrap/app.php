<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        /**
         * ============================
         * GLOBAL WEB MIDDLEWARE
         * ============================
         */
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,

            /**
             * ✅ POIN 91
             * User Behavior Pattern Logging
             * (login, action, route access)
             */
            \App\Http\Middleware\LogUserBehavior::class,
        ]);

        /**
         * ============================
         * MIDDLEWARE ALIAS
         * ============================
         */
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })

    /**
     * ============================
     * SCHEDULED TASK
     * ============================
     */
    ->withSchedule(function ($schedule) {
        $schedule->command('waba:sync-templates')->hourly();
    })

    /**
     * ============================
     * EXCEPTIONS
     * ============================
     */
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })

    ->create();
