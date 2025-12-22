<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SlaEscalationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $status
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // bisa tambah mail / wa
    }

    public function toArray($notifiable): array
    {
        return [
            'ticket_id'    => $this->ticket->id,
            'customer'     => $this->ticket->customer_name,
            'priority'     => $this->ticket->priority,
            'status'       => $this->status,
            'subject'      => $this->ticket->subject,
        ];
    }
}
