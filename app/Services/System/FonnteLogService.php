<?php

namespace App\Services\System;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;

class FonnteLogService
{
    public static function log(
        string $event,
        ?string $phone = null,
        ?int $sessionId = null,
        array $meta = []
    ): void {
        try {
            SystemLog::create([
                'event'       => $event,
                'entity_type' => 'whatsapp',
                'entity_id'   => $sessionId,
                'user_id'     => Auth::id(),
                'user_role'   => Auth::user()?->role ?? 'system',
                'meta'        => json_encode(array_merge([
                    'phone' => $phone,
                ], $meta)),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // LOG TIDAK BOLEH MEMBUNUH FLOW WA
        }
    }
}
