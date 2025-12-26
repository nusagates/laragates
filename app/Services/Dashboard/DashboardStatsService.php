<?php

namespace App\Services\Dashboard;

use App\Models\ChatSession;
use App\Models\User;

class DashboardStatsService
{
    /**
     * ===============================
     * DASHBOARD KPI STATS
     * ===============================
     */
    public function getStats(): array
    {
        return [
            /**
             * Active Chats
             * - sudah di-assign ke agent
             * - status masih open
             */
            'active_chats' => ChatSession::whereNotNull('assigned_to')
                ->where('status', 'open')
                ->count(),

            /**
             * Waiting Queue
             * - belum di-assign
             * - status masih open
             */
            'waiting_queue' => ChatSession::whereNull('assigned_to')
                ->where('status', 'open')
                ->count(),

            /**
             * Agents Online
             * - role agent
             * - status online
             * - is_active = 1
             */
            'agents_online' => User::where('role', 'agent')
                ->where('status', 'online')
                ->where('is_active', 1)
                ->count(),
        ];
    }

    /**
     * ===============================
     * WAITING QUEUE LIST (REAL DATA)
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

                $waitingMinutes = $session->created_at
                    ? $session->created_at->diffInMinutes(now(), true)
                    : 0;

                return [
                    'id'       => $session->id,
                    'customer' => $session->customer->name ?? 'Unknown',
                    'waiting'  => (int) $waitingMinutes,
                ];
            });
    }
}
