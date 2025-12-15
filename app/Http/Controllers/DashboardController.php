<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Ticket;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Total pesan hari ini
        $today_messages = ChatMessage::whereDate('created_at', today())->count();

        // Agen aktif
        $active_agents = User::where('role', 'agent')->count();

        // Ticket pending
        $pending_tickets = Ticket::where('status', 'pending')->count();

        // Recent Chats (TOP 5 lalu ambil last message)
        $recent_chats = ChatSession::query()
            ->select('chat_sessions.id', 'chat_sessions.customer_id', 'chat_sessions.created_at')
            ->with([
                'customer:id,name',
                'messages' => fn($q) => $q->latest()->limit(1)
            ])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'customer_name' => $session->customer->name ?? 'Unknown',
                    'last_message' => optional($session->messages->first())->message ?? '-',
                    'created_at' => $session->created_at,
                ];
            });

        // Status sistem (static dulu)
        $system_status = [
            'webhook' => true,
            'server' => true,
        ];

        return inertia('Dashboard/Index', [
            'today_messages'  => $today_messages,
            'active_agents'   => $active_agents,
            'pending_tickets' => $pending_tickets,
            'recent_chats'    => $recent_chats,
            'system_status'   => $system_status,
        ]);
    }
}
