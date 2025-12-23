<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

class SystemLogService
{
    public static function record(
    string $event,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $oldValues = null,
    ?array $newValues = null,
    array $meta = []
): void {
    try {
        \App\Models\SystemLog::create([
            'event'       => $event,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'user_id'     => auth()->id(),
            'user_role'   => auth()->user()->role ?? null,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'meta'        => $meta,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    } catch (\Throwable $e) {
        // silent fail
    }
}

}
