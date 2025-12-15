<?php

namespace App\Events;

use App\Models\TicketMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TicketMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TicketMessage $message;

    public function __construct(TicketMessage $message)
    {
        $this->message = $message->load('ticket', 'user');
    }

    public function broadcastOn()
    {
        return new Channel('tickets'); // public channel
    }

    public function broadcastAs()
    {
        return 'TicketMessageSent';
    }

    public function broadcastWith()
    {
        $t = $this->message->ticket;

        return [
            'ticket_id' => $t->id,
            'ticket' => [
                'id'            => $t->id,
                'customer_name' => $t->customer_name,
                'subject'       => $t->subject,
                'status'        => $t->status,
                'priority'      => $t->priority,
                'channel'       => $t->channel,
                'assigned_to'   => $t->assigned_to,
                'assigned_name' => $t->agent?->name,
                'last_message_at' => optional($t->last_message_at)->toIso8601String(),
                'updated_at'      => $t->updated_at->toIso8601String(),
            ],
        ];
    }
}
