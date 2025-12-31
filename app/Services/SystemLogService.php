<?php

namespace App\Services;

use App\Models\SystemLog;

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

            // Disable noisy route access
            if ($event === 'route_access') {
                return;
            }

            /**
             * SOURCE
             */
            $source = strtoupper(
                $meta['source']
                ?? (str_starts_with($event, 'sla_') ? 'SLA' : 'SYSTEM')
            );

            /**
             * LEVEL
             */
            $level = 'info';
            if (str_contains($event, 'breach')) {
                $level = 'critical';
            } elseif (str_contains($event, 'warning')) {
                $level = 'warning';
            }

            /**
             * DESCRIPTION (for UI)
             */
            $description = $meta['sla_type']
                ?? $entityType
                ?? null;

            SystemLog::create([
                'source'       => $source,
                'event'        => $event,
                'level'        => $level,
                'description'  => $description,
                'entity_type'  => $entityType,
                'entity_id'    => $entityId,
                'user_id'      => auth()->id(),
                'user_role'    => auth()->user()->role ?? null,
                'old_values'   => $oldValues,
                'new_values'   => $newValues,
                'meta'         => $meta,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);

        } catch (\Throwable $e) {
            // logging must never break business flow
        }
    }
}
