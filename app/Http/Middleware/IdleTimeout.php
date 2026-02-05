<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdleTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // hanya cek user login
        if (Auth::check()) {
            $now = time();

            // idle limit 15 menit (900 detik)
            $idleLimit = 15 * 60;

            // ambil last activity
            $lastActivity = session('last_activity');

            // kalau ada last activity & sudah lewat idle limit
            if ($lastActivity && ($now - $lastActivity) > $idleLimit) {
                Auth::logout();

                session()->invalidate();
                session()->regenerateToken();

                return redirect('/login')
                    ->withErrors([
                        'session' => 'Your session has expired due to inactivity.'
                    ]);
            }

            // update last activity
            session(['last_activity' => $now]);
        }

        return $next($request);
    }
}
