<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketSlaLog;
use Carbon\Carbon;

class TicketSlaService
{
    /**
     * ===============================
     * RUN SLA CHECK (SCHEDULED)
     * ===============================
     */
    public static function run(): void
    {
        $now = now();

        $tickets = Ticket::whereIn('status', ['pending', 'ongoing'])->get();

        foreach ($tickets as $ticket) {

            // ⛔ Safety guard
            if ($ticket->status === 'closed') {
                continue;
            }

            /**
             * ===============================
             * HITUNG WAKTU RESPONS
             * ===============================
             */
            $minutes = $ticket->last_message_at
                ? $ticket->last_message_at->diffInMinutes($now)
                : $ticket->created_at->diffInMinutes($now);

            /**
             * ===============================
             * SLA LIMIT PER PRIORITY
             * ===============================
             */
            $slaLimit = match ($ticket->priority) {
                'high'   => 30,
                'medium' => 60,
                'low'    => 120,
                default  => 60,
            };

            /**
             * ===============================
             * TENTUKAN STATUS SLA
             * ===============================
             */
            if ($minutes >= $slaLimit) {
                $status = 'breach';
            } elseif ($minutes >= ($slaLimit * 0.8)) {
                $status = 'warning';
            } else {
                continue; // masih aman
            }

            /**
             * ===============================
             * CEGAH DUPLIKASI LOG
             * (WARNING & BREACH DIPISAH)
             * ===============================
             */
            $alreadyLogged = TicketSlaLog::where('ticket_id', $ticket->id)
                ->where('rule', 'response_time')
                ->where('status', $status)
                ->exists();

            if ($alreadyLogged) {
                continue;
            }

            /**
             * ===============================
             * SIMPAN SLA LOG
             * ===============================
             */
            TicketSlaLog::create([
                'ticket_id'    => $ticket->id,
                'rule'         => 'response_time',
                'status'       => $status,
                'triggered_at' => $now,
                'meta'         => [
                    'minutes'  => $minutes,
                    'limit'    => $slaLimit,
                    'priority' => $ticket->priority,
                ],
            ]);

            /**
             * ===============================
             * UPDATE FLAG DI TICKET
             * ===============================
             */
            if ($status === 'warning') {
                $ticket->sla_warning_sent = true;
            }

            if ($status === 'breach') {
                $ticket->sla_breached = true;
            }

            $ticket->save();
        }
    }
}
