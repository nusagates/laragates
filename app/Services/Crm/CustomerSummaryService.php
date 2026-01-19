<?php

namespace App\Services\Crm;

use Carbon\Carbon;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Models\ChatMessage;

class CustomerSummaryService
{
    /**
     * Build CRM summary snapshot for customer
     */
    public function build(Customer $customer): array
    {
        return [
            'status'        => $this->resolveStatus($customer),
            'last_activity' => $this->lastActivity($customer),
            'counters'      => $this->counters($customer),
            'flags'         => $this->flags($customer),
        ];
    }

    /* =====================================================
       STATUS
    ===================================================== */
    protected function resolveStatus(Customer $customer): string
    {
        if ($customer->is_blacklisted) {
            return 'blocked';
        }

        if (
            $customer->last_contacted_at &&
            $customer->last_contacted_at->lt(now()->subDays(30))
        ) {
            return 'inactive';
        }

        return 'active';
    }

    /* =====================================================
       LAST ACTIVITY (HUMAN READABLE)
    ===================================================== */
    protected function lastActivity(Customer $customer): ?array
    {
        // Prefer explicit CRM timestamp
        if ($customer->last_contacted_at) {
            return [
                'text' => 'Last contacted ' . $customer->last_contacted_at->diffForHumans(),
                'at'   => $customer->last_contacted_at->toDateTimeString(),
            ];
        }

        // Fallback to system logs
        $log = SystemLog::where('entity_type', 'customer')
            ->where('entity_id', $customer->id)
            ->latest()
            ->first();

        if (!$log) {
            return null;
        }

        return [
            'text' => $this->humanizeEvent($log->event, $log->created_at),
            'at'   => $log->created_at->toDateTimeString(),
        ];
    }

    protected function humanizeEvent(string $event, Carbon $time): string
    {
        return match (true) {
            str_contains($event, 'broadcast') =>
                'Last broadcast ' . $time->diffForHumans(),

            str_contains($event, 'chat') =>
                'Last chat activity ' . $time->diffForHumans(),

            default =>
                'Last activity ' . $time->diffForHumans(),
        };
    }

    /* =====================================================
       COUNTERS (RELATION-CORRECT)
    ===================================================== */
    protected function counters(Customer $customer): array
    {
        // Ambil semua chat_session milik customer
        $sessionIds = $customer->chatSessions()
            ->pluck('id');

        return [
            'total_chats' => (int) ($customer->total_chats ?? 0),

            'messages_today' => ChatMessage::whereIn('chat_session_id', $sessionIds)
                ->whereDate('created_at', now())
                ->count(),
        ];
    }

    /* =====================================================
       FLAGS (CRM FRIENDLY)
    ===================================================== */
    protected function flags(Customer $customer): array
    {
        $flags = [];

        if ($customer->is_blacklisted) {
            $flags[] = 'Customer is blacklisted';
        }

        if ($customer->is_vip) {
            $flags[] = 'VIP customer';
        }

        if (
            $customer->last_contacted_at &&
            $customer->last_contacted_at->lt(now()->subDays(30))
        ) {
            $flags[] = 'No activity in last 30 days';
        }

        return $flags;
    }
}
