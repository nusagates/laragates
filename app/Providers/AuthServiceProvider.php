<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /* ===============================
           BASIC ROLE GATES
           =============================== */

        Gate::define('agent', function ($user) {
            return $user->hasAnyRole([
                'agent',
                'supervisor',
                'superadmin',
            ]);
        });

        Gate::define('supervisor', function ($user) {
            return $user->hasAnyRole([
                'supervisor',
                'superadmin',
            ]);
        });

        Gate::define('superadmin', function ($user) {
            return $user->isSuperAdmin();
        });

        /* ===============================
           FEATURE-LEVEL GATES (WABA)
           =============================== */

        // AI Conversation Summary
        Gate::define('use-ai-summary', function ($user) {
            return $user->hasAnyRole([
                'agent',
                'supervisor',
                'superadmin',
            ]);
        });

        // Chat reply
        Gate::define('send-message', function ($user) {
            return $user->hasAnyRole([
                'agent',
                'supervisor',
            ]);
        });

        // Ticket handling
        Gate::define('manage-ticket', function ($user) {
            return $user->hasAnyRole([
                'agent',
                'supervisor',
            ]);
        });

        // Broadcast approval
        Gate::define('approve-broadcast', function ($user) {
            return $user->hasAnyRole([
                'supervisor',
                'superadmin',
            ]);
        });

        // Template management
        Gate::define('manage-template', function ($user) {
            return $user->hasAnyRole([
                'supervisor',
                'superadmin',
            ]);
        });

        // Analytics access
        Gate::define('view-analytics', function ($user) {
            return $user->hasAnyRole([
                'supervisor',
                'superadmin',
            ]);
        });

        // User management
        Gate::define('manage-user', function ($user) {
            return $user->isSuperAdmin();
        });
    }
}
