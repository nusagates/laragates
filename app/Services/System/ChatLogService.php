<?php

namespace App\Services\System;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;

class ChatLogService
{
    public static function log(
        string $event,
        ?int $sessionId = null,
        array $meta = []
    ): void {
        try {
            SystemLog::create([
                'event'       => $event,
                'entity_type' => 'chat_session',
                'entity_id'   => $sessionId,
                'user_id'     => Auth::id(),
                'user_role'   => Auth::user()?->role ?? null,
                'meta'        => $meta ? json_encode($meta) : null,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // logging tidak boleh bikin error ke user
        }
    }
}
