<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            abort(403);
        }

        if (!in_array(auth()->user()->role, $roles)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
