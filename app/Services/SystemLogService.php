<?php

namespace App\Services;

use App\Models\SystemLog;

class SystemLogService
{
    /**
     * ==================================================
     * RECORD SYSTEM LOG (EVENT-BASED ONLY)
     * ==================================================
     */
    public static function record(
        string $event,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $meta = []
    ): void {
        try {

            /**
             * --------------------------------------------------
             * 🚫 DISABLE ROUTE ACCESS LOG COMPLETELY
             * --------------------------------------------------
             * We already log explicit business events
             * (menu_create, login_success, template_approve, etc)
             * so route_access is NO LONGER NEEDED.
             */
            if ($event === 'route_access') {
                return;
            }

            SystemLog::create([
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
            /**
             * SILENT FAIL
             * Logging must NEVER break business flow
             */
        }
    }
}
