<?php

namespace App\Services\SLA;

use App\Models\Ticket;
use App\Models\TicketSlaLog;

class TicketSlaEvaluator
{
    public function run(): void
    {
        $this->checkPendingTickets();
        $this->checkOngoingTickets();
    }

    /**
     * Pending terlalu lama → breach
     */
    protected function checkPendingTickets(): void
    {
        $limit = config('sla.tickets.pending_to_ongoing.max_minutes');

        Ticket::where('status', 'pending')
            ->get()
            ->each(function (Ticket $ticket) use ($limit) {

                $minutes = $ticket->created_at->diffInMinutes(now());

                if ($minutes >= $limit) {
                    $this->log(
                        $ticket,
                        'pending_to_ongoing',
                        'breach',
                        [
                            'minutes' => $minutes,
                        ]
                    );
                }
            });
    }

    /**
     * Ongoing terlalu lama → breach (berdasarkan priority)
     */
    protected function checkOngoingTickets(): void
    {
        $rules = config('sla.tickets.ongoing_to_closed');

        Ticket::where('status', 'ongoing')
            ->get()
            ->each(function (Ticket $ticket) use ($rules) {

                $priority = $ticket->priority ?? 'medium';
                $limit    = $rules[$priority] ?? $rules['medium'];

                $minutes = $ticket->updated_at->diffInMinutes(now());

                if ($minutes >= $limit) {
                    $this->log(
                        $ticket,
                        'ongoing_to_closed',
                        'breach',
                        [
                            'priority' => $priority,
                            'minutes'  => $minutes,
                        ]
                    );
                }
            });
    }

    /**
     * Simpan SLA log (idempotent)
     */
    protected function log(
        Ticket $ticket,
        string $rule,
        string $status,
        array $meta = []
    ): void {
        TicketSlaLog::firstOrCreate(
            [
                'ticket_id' => $ticket->id,
                'rule'      => $rule,
                'status'    => $status,
            ],
            [
                'triggered_at' => now(),
                'meta'         => $meta,
            ]
        );
    }
}
