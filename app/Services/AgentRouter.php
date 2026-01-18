<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgentRouter
{
    /**
     * Assign agent terbaik untuk chat handover
     */
    public static function assign(): ?int
    {
        return DB::transaction(function () {

            $agent = User::query()
                ->where('role', 'agent')
                ->where('is_online', true)
                ->where('last_heartbeat_at', '>=', now()->subSeconds(60))
                ->withCount([
                    'chatSessions as active_chats_count' => function ($q) {
                        $q->whereIn('status', ['open', 'pending']);
                    },
                ])
                ->orderBy('active_chats_count')
                ->orderBy('last_heartbeat_at', 'desc')
                ->lockForUpdate()
                ->first();

            return $agent?->id;
        });
    }

    /**
     * Assign langsung ke chat session
     */
    public static function assignToSession(ChatSession $session): ?int
    {
        return DB::transaction(function () use ($session) {

            $session->refresh();

            if ($session->assigned_to) {
                return $session->assigned_to;
            }

            $agentId = self::assign();

            if (! $agentId) {
                return null;
            }

            $session->update([
                'assigned_to' => $agentId,
                'status' => 'pending',
            ]);

            // Dispatch event untuk notifikasi realtime ke agent
            event(new \App\Events\SessionAssignedEvent($session->fresh()));

            return $agentId;
        });
    }
}
