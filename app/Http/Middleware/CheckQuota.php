<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LimitService;
use App\Exceptions\QuotaExceededException;

class CheckQuota
{
    public function handle(Request $request, Closure $next, string $key, int $amount = 1)
    {
        try {
            LimitService::check($key, $amount);
        } catch (QuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'QUOTA_EXCEEDED'
            ], 402);
        }

        $response = $next($request);

        if ($response->status() < 400) {
            LimitService::consume($key, $amount);
        }

        return $response;
    }
}
