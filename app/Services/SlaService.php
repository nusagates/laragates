<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\SystemLogService;

class SlaService
{
    /**
     * ===============================
     * FIRST RESPONSE SLA
     * ===============================
     */
    public static function recordFirstResponse(ChatSession $session): void
    {
        // Anti spam / double record
        if ($session->first_response_at !== null) {
            return;
        }

        // Ambil pesan agent pertama
        $firstAgentMessage = ChatMessage::query()
            ->where('chat_session_id', $session->id)
            ->where('is_outgoing', true)
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$firstAgentMessage) {
            return;
        }

        $seconds   = $session->created_at->diffInSeconds($firstAgentMessage->created_at);
        $threshold = config('sla.first_response_minutes') * 60;

        $status = $seconds <= $threshold ? 'meet' : 'breach';

        /**
         * ===============================
         * UPDATE SESSION (WAJIB)
         * ===============================
         */
        $session->update([
            'first_response_at'      => $firstAgentMessage->created_at,
            'first_response_seconds' => $seconds,
            'sla_status'             => $status, // ✅ FIX PENTING
        ]);

        /**
         * ===============================
         * SYSTEM LOG
         * ===============================
         */
        SystemLogService::record(
            event: $status === 'meet'
                ? 'sla_first_response_meet'
                : 'sla_first_response_breach',
            entityType: 'chat_session',
            entityId: $session->id,
            oldValues: null,
            newValues: [
                'actual_seconds'    => $seconds,
                'threshold_seconds' => $threshold,
                'sla_status'        => $status,
            ],
            meta: [
                'source'    => 'sla',
                'sla_type'  => 'first_response',
                'agent_id'  => $session->assigned_to,
            ]
        );
    }

    /**
     * ===============================
     * RESOLUTION SLA
     * ===============================
     */
    public static function recordResolution(ChatSession $session): void
    {
        if (!$session->closed_at) {
            return;
        }

        // Anti spam
        if ($session->resolution_seconds !== null) {
            return;
        }

        $resolutionSeconds = $session->created_at->diffInSeconds($session->closed_at);

        $firstResponseLimit = config('sla.first_response_minutes') * 60;
        $resolutionLimit    = config('sla.resolution_hours') * 3600;

        $status =
            $session->first_response_seconds !== null &&
            $session->first_response_seconds <= $firstResponseLimit &&
            $resolutionSeconds <= $resolutionLimit
                ? 'meet'
                : 'breach';

        /**
         * ===============================
         * UPDATE SESSION
         * ===============================
         */
        $session->update([
            'resolution_seconds' => $resolutionSeconds,
            'sla_status'         => $status,
        ]);

        /**
         * ===============================
         * SYSTEM LOG
         * ===============================
         */
        SystemLogService::record(
            event: $status === 'meet'
                ? 'sla_resolution_meet'
                : 'sla_resolution_breach',
            entityType: 'chat_session',
            entityId: $session->id,
            oldValues: null,
            newValues: [
                'actual_seconds'    => $resolutionSeconds,
                'threshold_seconds' => $resolutionLimit,
                'sla_status'        => $status,
            ],
            meta: [
                'source'    => 'sla',
                'sla_type'  => 'resolution',
                'agent_id'  => $session->assigned_to,
            ]
        );
    }
}
