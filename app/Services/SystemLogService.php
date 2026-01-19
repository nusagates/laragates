<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;

class SystemLogService
{
    /**
     * ==================================================
     * MAIN LOGGER (COMPLIANCE & AUDIT SAFE)
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
             * ===============================
             * SKIP NOISY LOGS
             * ===============================
             */
            if ($event === 'route_access') {
                return;
            }

            /**
             * ===============================
             * SOURCE CLASSIFICATION
             * ===============================
             */
            $source = strtoupper(
                $meta['source']
                ?? match (true) {
                    str_starts_with($event, 'sla_')        => 'SLA',
                    str_starts_with($event, 'contact_')    => 'CONTACT',
                    str_starts_with($event, 'chat_')       => 'CHAT',
                    str_starts_with($event, 'security_')   => 'SECURITY',
                    default                                => 'SYSTEM',
                }
            );

            /**
             * ===============================
             * LEVEL CLASSIFICATION
             * ===============================
             */
            $level = match (true) {
                str_contains($event, 'breach')     => 'critical',
                str_contains($event, 'blacklist')  => 'warning',
                str_contains($event, 'failed')     => 'warning',
                str_contains($event, 'denied')     => 'warning',
                default                             => 'info',
            };

            /**
             * ===============================
             * DESCRIPTION (UI FRIENDLY)
             * ===============================
             */
            $description =
                $meta['description']
                ?? $meta['sla_type']
                ?? $entityType
                ?? null;

            /**
             * ===============================
             * CREATE LOG
             * ===============================
             */
            SystemLog::create([
                'source'       => $source,
                'event'        => $event,
                'level'        => $level,
                'description'  => $description,
                'entity_type'  => $entityType,
                'entity_id'    => $entityId,
                'user_id'      => Auth::id(),
                'user_role'    => Auth::user()->role ?? null,
                'old_values'   => $oldValues,
                'new_values'   => $newValues,
                'meta'         => $meta,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);

        } catch (\Throwable $e) {
            /**
             * ===============================
             * FAIL SILENTLY (MANDATORY)
             * ===============================
             * Logging must NEVER break business flow
             */
        }
    }

    /**
     * ==================================================
     * SHORTCUT HELPERS (OPTIONAL)
     * ==================================================
     */

    public static function info(
        string $event,
        ?string $entityType = null,
        ?int $entityId = null,
        array $meta = []
    ): void {
        self::record($event, $entityType, $entityId, null, null, $meta);
    }

    public static function warning(
        string $event,
        ?string $entityType = null,
        ?int $entityId = null,
        array $meta = []
    ): void {
        self::record($event, $entityType, $entityId, null, null, array_merge($meta, [
            'forced_level' => 'warning',
        ]));
    }

    public static function critical(
        string $event,
        ?string $entityType = null,
        ?int $entityId = null,
        array $meta = []
    ): void {
        self::record($event, $entityType, $entityId, null, null, array_merge($meta, [
            'forced_level' => 'critical',
        ]));
    }

    public function sources()
{
    return \App\Models\SystemLog::query()
        ->select('source')
        ->distinct()
        ->orderBy('source')
        ->pluck('source');
}

}
