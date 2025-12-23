<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

class SystemLogService
{
    public static function log(
        string $event,
        ?string $entityType = null,
        ?int $entityId = null,
        array $meta = [],
        array $oldValues = null,
        array $newValues = null
    ): void {
        try {
            $user = Auth::user();

            SystemLog::create([
                'event'       => $event,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'user_id'     => $user?->id,
                'user_role'   => $user?->role,
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'meta'        => $meta,
                'ip_address'  => Request::ip(),
                'user_agent'  => substr(Request::userAgent(), 0, 255),
            ]);
        } catch (Throwable $e) {
            // ❌ logging gagal TIDAK BOLEH ganggu sistem
        }
    }
}
