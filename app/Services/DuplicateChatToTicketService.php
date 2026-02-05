<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Ticket;

class DuplicateChatToTicketService
{
    /**
     * Handle duplication of chat message to ticket.
     * Creates a ticket on first incoming customer message if one doesn't exist.
     * Creates TicketMessage for both incoming and outgoing messages.
     */
    public static function duplicateMessage(ChatMessage $message): ?Ticket
    {
        $session = $message->session;

        // Only duplicate for customer/agent messages (skip system messages)
        if (! in_array($message->sender, ['customer', 'agent'])) {
            return null;
        }

        // Get or create ticket on first incoming customer message
        $ticket = $session->ticket;
        if (! $ticket && $message->sender === 'customer' && ! $message->is_outgoing) {
            $ticket = self::createTicketFromSession($session);
        }

        // If ticket exists, create ticket message
        if ($ticket) {
            self::createTicketMessage($ticket, $message);

            // Update ticket's last_message_at timestamp
            $ticket->update(['last_message_at' => now()]);
        }

        return $ticket;
    }

    /**
     * Create a new Ticket from a ChatSession on first incoming message
     */
    private static function createTicketFromSession(ChatSession $session): Ticket
    {
        $customer = $session->customer;

        return Ticket::create([
            'chat_session_id' => $session->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'subject' => 'WhatsApp: '.$customer->name,
            'status' => 'pending',
            'priority' => 'medium',
            'channel' => 'whatsapp',
            'assigned_to' => null,
        ]);
    }

    /**
     * Create a TicketMessage from a ChatMessage
     */
    private static function createTicketMessage(Ticket $ticket, ChatMessage $message): void
    {
        // Determine sender type based on ChatMessage sender
        $senderType = match ($message->sender) {
            'agent' => 'agent',
            'customer' => 'customer',
            default => 'customer',
        };

        // Get sender_id based on message type
        $senderId = match ($message->sender) {
            'agent' => $message->user_id,
            'customer' => null, // Customer messages don't have a sender_id in tickets
            default => null,
        };

        $ticket->messages()->create([
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $message->message,
            'sent_at' => $message->created_at,
        ]);
    }

    /**
     * Sync ChatSession status with linked Ticket status
     */
    public static function syncStatusToTicket(ChatSession $session): void
    {
        $ticket = $session->ticket;

        if (! $ticket) {
            return;
        }

        // Map ChatSession status to Ticket status
        $ticketStatus = match ($session->status) {
            'open' => 'ongoing',  // Open chat session = ongoing ticket
            'pending' => 'pending',
            'closed' => 'closed',
            default => 'pending',
        };

        if ($ticket->status !== $ticketStatus) {
            $ticket->update(['status' => $ticketStatus]);
        }
    }

    /**
     * Sync Ticket status to ChatSession status
     */
    public static function syncStatusFromTicket(Ticket $ticket): void
    {
        $session = ChatSession::where('id', $ticket->chat_session_id)->first();

        if (! $session) {
            return;
        }

        // Map Ticket status to ChatSession status
        $sessionStatus = match ($ticket->status) {
            'ongoing' => 'open',  // Ongoing ticket = open chat session
            'pending' => 'pending',
            'closed' => 'closed',
            default => 'pending',
        };

        if ($session->status !== $sessionStatus) {
            $session->update(['status' => $sessionStatus]);
        }
    }
}
