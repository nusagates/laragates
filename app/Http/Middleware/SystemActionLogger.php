<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemLogService;

class SystemActionLogger
{
    public function handle(Request $request, Closure $next)
    {
        // lanjut dulu request
        $response = $next($request);

        // hanya log jika user login
        if (!Auth::check()) {
            return $response;
        }

        // skip noise
        if (
            $request->is('agent/heartbeat') ||
            $request->is('broadcast*') && $request->isMethod('GET')
        ) {
            return $response;
        }

        $route = optional($request->route())->getName();

        if (!$route) {
            return $response;
        }

        SystemLogService::log(
            event: $route,
            entityType: null,
            entityId: null,
            old: null,
            new: null,
            meta: [
                'method' => $request->method(),
                'path'   => $request->path(),
                'input'  => $request->except(['password','token']),
                'status' => $response->getStatusCode(),
            ],
            request: $request
        );

        return $response;
    }
}
