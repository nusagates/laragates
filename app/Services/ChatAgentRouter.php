<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChatSession;
use App\Models\ChatPriority;

class ChatAgentRouter
{
    /**
     * Cari agent terbaik untuk chat session customer.
     */
    public function findBestAgent(int $customerId, ?string $intent = null): ?User
    {
        // Ambil prioritas customer (normal jika tidak ada)
        $priority = ChatPriority::where('customer_id', $customerId)->value('priority') ?? 'normal';

        // Ambil agent aktif + online + aktif
        $agents = User::query()
            ->where('role', 'agent')
            ->where('status', 'online')
            ->where('is_active', true)
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        // Hitung workload: jumlah session OPEN
        $agents = $agents->map(function ($agent) {
            $agent->workload = ChatSession::where('assigned_to', $agent->id)
                                          ->where('status', 'open')
                                          ->count();
            return $agent;
        });

        // Filter berdasarkan skill jika intent tersedia
        if ($intent) {
            $matched = $agents->filter(function ($agent) use ($intent) {
                $skills = $agent->skills ?? [];
                return in_array($intent, $skills, true);
            });

            if ($matched->isNotEmpty()) {
                $agents = $matched;
            }
        }

        // Urutkan berdasarkan workload (terkecil)
        return $agents->sortBy('workload')->first();
    }

    /**
     * Assign (atau pending) ke session
     */
    public function assignSession(ChatSession $session, ?string $intent = null): ChatSession
    {
        $agent = $this->findBestAgent($session->customer_id, $intent);

        if ($agent) {
            $session->assigned_to = $agent->id;
            $session->status = 'open';
        } else {
            $session->status = 'pending';
        }

        $session->save();

        // 🔥 Kirim notifikasi realtime ke agent yang terpilih
        event(new \App\Events\SessionAssignedEvent($session));

        return $session;
    }

    /**
     * Ketika agent online, ambil pending sessions sesuai workload
     */
    public function assignPendingTo(User $agent): void
    {
        // Ambil semua pending yg belum ada assigned_to
        $pendingSessions = ChatSession::whereNull('assigned_to')
                                      ->where('status', 'pending')
                                      ->orderBy('created_at', 'asc')
                                      ->get();

        foreach ($pendingSessions as $session) {

            // Hitung workload agent
            $workload = ChatSession::where('assigned_to', $agent->id)
                                   ->where('status', 'open')
                                   ->count();

            // Batas workload (sementara max 5)
            if ($workload >= 5) {
                break;
            }

            // Assign session (automatically triggers event)
            $this->assignSession($session);
        }
    }
}
