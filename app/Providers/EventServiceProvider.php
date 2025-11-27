<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Models\User;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        //
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // =====================================================
        // EVENT: AUTO ONLINE WHEN LOGIN
        // =====================================================
        Event::listen(Login::class, function ($event) {
            if (!($event->user instanceof User)) return;

            // Jika belum approval → tetap PENDING
            if (empty($event->user->approved_at)) {
                $event->user->update([
                    'status'     => 'pending',
                    'is_active'  => false,
                    'last_seen'  => now(),
                ]);
                return;
            }

            // Jika sudah approval → ONLINE
            $event->user->update([
                'status'     => 'online',
                'is_active'  => true,
                'last_seen'  => now(),
            ]);
        });

        // =====================================================
        // EVENT: AUTO OFFLINE WHEN LOGOUT
        // =====================================================
        Event::listen(Logout::class, function ($event) {
            if (!($event->user instanceof User)) return;

            // Kalau user belum approved → status tetap pending
            if (empty($event->user->approved_at)) {
                $event->user->update([
                    'status'     => 'pending',
                    'last_seen'  => now(),
                ]);
                return;
            }

            // Jika approved → set offline
            $event->user->update([
                'status'     => 'offline',
                'last_seen'  => now(),
            ]);
        });
    }
}
