<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Ticket;
use App\Services\Dashboard\DashboardStatsService;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $statsService)
    {
        /**
         * ===============================
         * KPI LEGACY
         * ===============================
         */
        $today_messages = ChatMessage::whereDate('created_at', today())->count();
        $pending_tickets = Ticket::where('status', 'pending')->count();

        /**
         * ===============================
         * DASHBOARD STATS (REAL)
         * ===============================
         */
        $stats = $statsService->getStats();

        /**
         * ===============================
         * WAITING QUEUE (REAL)
         * ===============================
         */
        $waitingQueue = $statsService->getWaitingQueue();

        /**
         * ===============================
         * RECENT CHATS (OPTIONAL)
         * ===============================
         */
        $recent_chats = ChatSession::query()
            ->with([
                'customer:id,name',
                'messages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id'            => $session->id,
                    'customer_name' => $session->customer->name ?? 'Unknown',
                    'last_message'  => optional($session->messages->first())->message ?? '-',
                    'created_at'    => $session->created_at,
                ];
            });

        /**
         * ===============================
         * RENDER INERTIA
         * ===============================
         * ⚠️ FILE VUE: resources/js/pages/dashboard.vue
         * ⚠️ MAKA inertia('dashboard')
         */
        return inertia('Dashboard', [
            'today_messages'      => $today_messages,
            'pending_tickets'     => $pending_tickets,

            // KPI utama
            'stats'               => $stats,

            // Waiting queue list
            'waiting_queue_list'  => $waitingQueue,

            // Optional
            'recent_chats'        => $recent_chats,
        ]);
    }
}
