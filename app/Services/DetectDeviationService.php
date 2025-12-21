<?php

namespace App\Services;

use App\Models\UserBehaviorLog;
use Illuminate\Support\Facades\Log;

class DetectDeviationService
{
    public static function analyze($user, string $action, string $path): array
    {
        try {

            /**
             * ===============================
             * 1. SKIP SYSTEM / HEARTBEAT
             * ===============================
             */
            if (
                str_contains($path, 'heartbeat') ||
                str_contains($path, 'offline') ||
                str_contains($path, 'broadcast') ||
                str_contains($path, 'analytics')
            ) {
                return self::normal();
            }

            /**
             * ===============================
             * 2. SIMPLE RATE CHECK (SAFE)
             * ===============================
             * ⚠️ Jangan agresif
             * ⚠️ Jangan query berat
             */
            $recentCount = UserBehaviorLog::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMinute())
                ->limit(30) // HARD LIMIT (ANTI DoS DB)
                ->count();

            if ($recentCount > 20) {
                return [
                    'is_deviation' => true,
                    'risk_level'   => 'medium',
                    'meta' => [
                        'reason' => 'high_request_rate',
                        'count'  => $recentCount,
                    ],
                ];
            }

            /**
             * ===============================
             * 3. DEFAULT = NORMAL
             * ===============================
             */
            return self::normal();

        } catch (\Throwable $e) {

            /**
             * ===============================
             * 4. FAIL SAFE
             * ===============================
             * Kalau error → JANGAN BLOCK USER
             */
            Log::warning('Deviation detection failed', [
                'user_id' => $user?->id,
                'error'   => $e->getMessage(),
            ]);

            return self::normal();
        }
    }

    private static function normal(): array
    {
        return [
            'is_deviation' => false,
            'risk_level'   => 'low',
            'meta'         => [],
        ];
    }
}
