<?php

namespace App\Services\Dashboard;

use App\Models\ChatSession;
use App\Models\User;

class DashboardStatsService
{
    /**
     * ===============================
     * KPI STATS
     * ===============================
     */
    public function getStats(): array
    {
        return [
            'active_chats' => ChatSession::whereNotNull('assigned_to')
                ->where('status', 'open')
                ->count(),

            'waiting_queue' => ChatSession::whereNull('assigned_to')
                ->where('status', 'open')
                ->count(),

            'agents_online' => User::where('role', 'agent')
                ->where('status', 'online')
                ->where('is_active', 1)
                ->count(),
        ];
    }

    /**
     * ===============================
     * WAITING QUEUE LIST
     * ===============================
     */
    public function getWaitingQueue(int $limit = 10)
    {
        return ChatSession::query()
            ->whereNull('assigned_to')
            ->where('status', 'open')
            ->with('customer:id,name')
            ->orderBy('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($session) {

                $minutes = $session->created_at
                    ? $session->created_at->diffInMinutes(now(), true)
                    : 0;

                return [
                    'id'       => $session->id,
                    'customer' => $session->customer->name ?? 'Unknown',
                    'waiting'  => (int) $minutes,
                ];
            });
    }

    /**
     * ===============================
     * AGENT STATUS
     * ===============================
     */
    public function getAgentStatus(int $limit = 10)
    {
        return User::query()
            ->where('role', 'agent')
            ->where('is_active', 1)
            ->withCount([
                'chatSessions as active_chats' => function ($q) {
                    $q->where('status', 'open');
                }
            ])
            ->orderByDesc('active_chats')
            ->limit($limit)
            ->get()
            ->map(fn ($agent) => [
                'id'           => $agent->id,
                'name'         => $agent->name,
                'active_chats' => $agent->active_chats,
                'status'       => $agent->status,
            ]);
    }
}
