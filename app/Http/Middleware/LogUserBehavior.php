<?php

namespace App\Http\Middleware;

use App\Support\BehaviorLogger;
use App\Services\DetectDeviationService;
use App\Models\UserBehaviorLog;
use Closure;
use Illuminate\Http\Request;

class LogUserBehavior
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $user = auth()->user();
        if (!$user) {
            return $response;
        }

        // =============================
        // BASIC REQUEST LOG (POIN 91)
        // =============================
        BehaviorLogger::log(
            'REQUEST',
            $request,
            [
                'status_code' => $response->getStatusCode(),
            ]
        );

        // =============================
        // DEVIATION ANALYSIS (POIN 97)
        // =============================
        $action = $request->method() . ' ' . $request->path();

        $analysis = DetectDeviationService::analyze(
            $user,
            $action,
            $request->path()
        );

        UserBehaviorLog::create([
            'user_id'      => $user->id,
            'action'       => $action,
            'route'        => $request->path(),
            'method'       => $request->method(),
            'ip'           => $request->ip(),
            'user_agent'   => substr((string) $request->userAgent(), 0, 255),
            'is_deviation' => $analysis['is_deviation'],
            'risk_level'   => $analysis['risk_level'],
            'meta'         => $analysis['meta'],
        ]);

        return $response;
    }
}
