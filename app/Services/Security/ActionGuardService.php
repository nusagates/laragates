<?php

namespace App\Services\Security;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ActionGuardService
{
    /**
     * Per-action enforcement (cooldown & hard deny)
     */
    public static function check(string $action, int $limitPerMinute = 5): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // HARUS sama dengan RateMonitorService
        $window = now()->format('YmdHi');
        $rateKey = "rate:{$action}:user:{$user->id}:{$window}";

        $count = Cache::get($rateKey, 0);

        // HARD BLOCK (critical)
        if ($count >= 10) {
            self::logDenied($action, 'critical_block', $count, $limitPerMinute);

            throw new HttpException(
                429,
                'Aktivitas diblokir sementara (rate terlalu tinggi).'
            );
        }

        // SOFT BLOCK (cooldown)
        if ($count >= $limitPerMinute) {
            self::logDenied($action, 'cooldown', $count, $limitPerMinute);

            throw new HttpException(
                429,
                'Aktivitas terlalu cepat. Silakan tunggu sebentar.'
            );
        }
    }

    protected static function logDenied(
        string $action,
        string $reason,
        int $count,
        int $limit
    ): void {
        SystemLog::create([
            'event'      => 'action_denied',
            'user_id'    => Auth::id(),
            'user_role'  => Auth::user()->role ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta'       => json_encode([
                'action' => $action,
                'reason' => $reason,
                'count'  => $count,
                'limit'  => $limit,
                'window' => '1_minute',
            ]),
        ]);
    }
}
