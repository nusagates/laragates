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
    | MIDDLEWARE CONFIGURATION
    |--------------------------------------------------------------------------
    */
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------
        | GLOBAL WEB MIDDLEWARE STACK
        |--------------------------------------------------
        */
        $middleware->web(append: [

            // Inertia.js
            \App\Http\Middleware\HandleInertiaRequests::class,

            // Laravel default
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,

            // User behavior & system logging (read-only)
            \App\Http\Middleware\LogUserBehavior::class,
        ]);

        /*
        |--------------------------------------------------
        | MIDDLEWARE ALIAS (SEMUA ALIAS HARUS DI SINI)
        |--------------------------------------------------
        */
        $middleware->alias([

            // Role & access control
            'role' => \App\Http\Middleware\CheckRole::class,

            // Quota limiter (API)
            'quota' => CheckQuota::class,

            // Email verification (verified OR grace period)
            'verified_or_grace' => \App\Http\Middleware\EnsureVerifiedOrInGracePeriod::class,

            // 🔒 Soft suspend (block inactive users)
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | SCHEDULED TASKS (Laravel 12)
    |--------------------------------------------------------------------------
    */
    ->withSchedule(function ($schedule) {

        // Existing scheduler
        $schedule->command('waba:sync-templates')->hourly();

        // 🔒 Auto suspend unverified users (grace expired)
        $schedule->command('users:auto-suspend-unverified')->hourly();
    })

    /*
    |--------------------------------------------------------------------------
    | EXCEPTION HANDLER
    |--------------------------------------------------------------------------
    */
    ->withExceptions(function (Exceptions $exceptions): void {
        // default handler (biarkan kosong)
    })

    ->create();
