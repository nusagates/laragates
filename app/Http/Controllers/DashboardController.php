<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Ticket;
use App\Services\Dashboard\DashboardStatsService;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $statsService)
    {
        $today_messages = ChatMessage::whereDate('created_at', today())->count();
        $pending_tickets = Ticket::where('status', 'pending')->count();

        $stats        = $statsService->getStats();
        $waitingQueue = $statsService->getWaitingQueue();
        $agents       = $statsService->getAgentStatus();

        return inertia('Dashboard', [
            'today_messages'     => $today_messages,
            'pending_tickets'    => $pending_tickets,
            'stats'              => $stats,
            'waiting_queue_list' => $waitingQueue,
            'agents'             => $agents,
        ]);
    }
}
