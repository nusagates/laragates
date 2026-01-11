<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVerifiedOrInGracePeriod
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (
            $user &&
            (
                $user->hasVerifiedEmail()
                || $user->isInEmailVerificationGracePeriod()
            )
        ) {
            return $next($request);
        }

        abort(403, 'Email verification required');
    }
}
