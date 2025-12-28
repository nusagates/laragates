<?php

namespace App\Services\Security;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class RateMonitorService
{
    /**
     * ===============================
     * RATE CHECK ENTRY POINT
     * ===============================
     */
    public static function check(string $action, int $limitPerMinute = 10): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        /**
         * WINDOW = per minute (audit friendly)
         */
        $windowKey = now()->format('YmdHi');
        $key = "rate:{$action}:user:{$user->id}:{$windowKey}";

        // SAFE counter
        $count = Cache::get($key, 0) + 1;

        Cache::put(
            $key,
            $count,
            now()->addSeconds(65) // 1 minute + buffer
        );

        /**
         * ===============================
         * ESCALATION ONLY (NO DOUBLE LOG)
         * ===============================
         */
        if ($count > $limitPerMinute) {
            self::logEscalation($action, $count, $limitPerMinute);
        }
    }

    /**
     * ===============================
     * ESCALATION LOGGER
     * ===============================
     */
    protected static function logEscalation(string $action, int $count, int $limit): void
    {
        $event = match (true) {
            $count >= 10 => 'security_critical',
            $count >= 6  => 'security_warning',
            default      => 'suspicious_rate',
        };

        SystemLog::create([
            'event'      => $event,
            'user_id'    => Auth::id(),
            'user_role'  => Auth::user()->role ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => request()->userAgent(),
            'meta'       => json_encode([
                'action' => $action,
                'count'  => $count,
                'limit'  => $limit,
                'window' => '1_minute',
            ]),
        ]);
    }
}
