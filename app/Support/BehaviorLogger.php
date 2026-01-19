<?php

namespace App\Support;

use App\Models\UserBehaviorLog;
use Illuminate\Http\Request;

class BehaviorLogger
{
    public static function log(
        string $action,
        ?Request $request = null,
        array $meta = []
    ): void {
        try {
            $user = auth()->user();

            UserBehaviorLog::create([
                'user_id'    => $user?->id,
                'role'       => $user?->role,
                'action'     => $action,
                'endpoint'   => $request?->path(),
                'method'     => $request?->method(),
                'ip'         => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'meta'       => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            // ❗ Jangan ganggu flow utama (compliance-safe)
            logger()->warning('BehaviorLogger failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
