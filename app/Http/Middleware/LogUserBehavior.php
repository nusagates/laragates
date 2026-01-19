<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SystemLog;
use Throwable;

class LogUserBehavior
{
    public function handle(Request $request, Closure $next)
    {
        try {

            // ===============================
            // SKIP REQUEST YANG AMAN
            // ===============================
            if (
                $request->is('agent/heartbeat') ||
                $request->is('agent/offline') ||
                $request->is('webhook/*') ||
                $request->is('_debugbar/*') ||
                $request->is('up')
            ) {
                return $next($request);
            }

            $user = $request->user();

            // ===============================
            // ✅ FIX 1: LOG SEBELUM $next()
            // ===============================
            SystemLog::create([
                'event'       => 'route_access',
                'entity_type' => null,
                'entity_id'   => null,
                'user_id'     => $user?->id,
                'user_role'   => $user?->role,
                'meta'        => [
                    'method'  => $request->method(),
                    'path'    => $request->path(),
                    'route'   => optional($request->route())->getName(),
                    'inertia' => (bool) $request->header('X-Inertia'),
                ],
                'ip_address'  => $request->ip(),
                'user_agent'  => substr($request->userAgent(), 0, 255),
            ]);

        } catch (Throwable $e) {
            // ❌ LOG BOLEH GAGAL — SISTEM JANGAN
        }

        return $next($request);
    }
}
