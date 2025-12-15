<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Not authenticated.');
        }

        // Normalize role names (case insensitive)
        $userRole = strtolower(trim($user->role));
        $allowedRoles = array_map(fn($r) => strtolower(trim($r)), $roles);

        // Check if user role is in allowed list
        if (!in_array($userRole, $allowedRoles)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
