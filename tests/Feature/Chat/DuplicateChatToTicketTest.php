<?php

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\DuplicateChatToTicketService;

beforeEach(function () {
    $this->agent = User::factory()->create(['role' => 'agent']);

    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $this->session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'status' => 'open',
    ]);
});

it('creates a ticket on first incoming customer message', function () {
    expect($this->session->ticket)->toBeNull();

    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello, I need help!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);

    DuplicateChatToTicketService::duplicateMessage($message);

    $this->session->refresh();
    expect($this->session->ticket)->not->toBeNull();
    expect($this->session->ticket->customer_name)->toBe('Test Customer');
    expect($this->session->ticket->customer_phone)->toBe('6281234567890');
    expect($this->session->ticket->channel)->toBe('whatsapp');
    expect($this->session->ticket->status)->toBe('pending');
});

it('creates a ticket message when duplicating customer message', function () {
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello, I need help!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);

    DuplicateChatToTicketService::duplicateMessage($message);

    $ticket = $this->session->ticket;
    expect($ticket->messages)->toHaveCount(1);

    $ticketMsg = $ticket->messages->first();
    expect($ticketMsg->sender_type)->toBe('customer');
    expect($ticketMsg->sender_id)->toBeNull();
    expect($ticketMsg->message)->toBe('Hello, I need help!');
});

it('creates a ticket message when agent sends message to existing ticket', function () {
    // Create first customer message to create ticket
    $customerMsg = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);
    DuplicateChatToTicketService::duplicateMessage($customerMsg);

    $ticket = $this->session->ticket;
    expect($ticket->messages)->toHaveCount(1);

    // Now agent sends message
    $agentMsg = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'agent',
        'user_id' => $this->agent->id,
        'message' => 'Hi! How can we help?',
        'type' => 'text',
        'is_outgoing' => true,
        'status' => 'sent',
    ]);

    DuplicateChatToTicketService::duplicateMessage($agentMsg);

    $ticket->refresh();
    expect($ticket->messages)->toHaveCount(2);

    $agentTicketMsg = $ticket->messages->last();
    expect($agentTicketMsg->sender_type)->toBe('agent');
    expect($agentTicketMsg->sender_id)->toBe($this->agent->id);
    expect($agentTicketMsg->message)->toBe('Hi! How can we help?');
});

it('does not create ticket for system messages', function () {
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Connected to agent',
        'type' => 'text',
        'is_outgoing' => true,
        'status' => 'sent',
    ]);

    DuplicateChatToTicketService::duplicateMessage($message);

    $this->session->refresh();
    expect($this->session->ticket)->toBeNull();
});

it('does not create ticket for outgoing customer messages', function () {
    // Note: This is an edge case - normally customer messages are incoming
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'This is not a normal incoming message',
        'type' => 'text',
        'is_outgoing' => true,  // Outgoing flag makes this not a first message
        'status' => 'received',
    ]);

    DuplicateChatToTicketService::duplicateMessage($message);

    $this->session->refresh();
    expect($this->session->ticket)->toBeNull();
});

it('does not duplicate to ticket if ticket does not exist for non-first messages', function () {
    // Create a chat session without a ticket
    $anotherSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'status' => 'open',
    ]);

    $agentMsg = ChatMessage::create([
        'chat_session_id' => $anotherSession->id,
        'sender' => 'agent',
        'user_id' => $this->agent->id,
        'message' => 'This should not create a ticket',
        'type' => 'text',
        'is_outgoing' => true,
        'status' => 'sent',
    ]);

    DuplicateChatToTicketService::duplicateMessage($agentMsg);

    $anotherSession->refresh();
    expect($anotherSession->ticket)->toBeNull();
    expect(TicketMessage::count())->toBe(0);
});

it('syncs chat session status to ticket status', function () {
    // Create ticket
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Help me!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);
    DuplicateChatToTicketService::duplicateMessage($message);

    $ticket = $this->session->ticket;
    expect($ticket->status)->toBe('pending');

    // Change session status to closed
    $this->session->update(['status' => 'closed']);
    DuplicateChatToTicketService::syncStatusToTicket($this->session);

    $ticket->refresh();
    expect($ticket->status)->toBe('closed');
});

it('maps chat session open status to ticket ongoing status', function () {
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Help!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);
    DuplicateChatToTicketService::duplicateMessage($message);

    $ticket = $this->session->ticket;

    // Change session status to open (which should map to ongoing in ticket)
    $this->session->update(['status' => 'open']);
    DuplicateChatToTicketService::syncStatusToTicket($this->session);

    $ticket->refresh();
    expect($ticket->status)->toBe('ongoing');
});

it('syncs ticket status to chat session status', function () {
    // Create ticket
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);
    DuplicateChatToTicketService::duplicateMessage($message);

    $ticket = $this->session->ticket;

    // Change ticket status to closed
    $ticket->update(['status' => 'closed']);
    DuplicateChatToTicketService::syncStatusFromTicket($ticket);

    $this->session->refresh();
    expect($this->session->status)->toBe('closed');
});

it('updates ticket last_message_at on new message', function () {
    $message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);

    DuplicateChatToTicketService::duplicateMessage($message);

    $ticket = $this->session->ticket;
    expect($ticket->last_message_at)->not->toBeNull();
});

it('only creates one ticket per chat session', function () {
    // First message creates ticket
    $msg1 = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello!',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);
    DuplicateChatToTicketService::duplicateMessage($msg1);

    expect(Ticket::where('chat_session_id', $this->session->id)->count())->toBe(1);

    // Second message should not create another ticket
    $msg2 = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Still here?',
        'type' => 'text',
        'is_outgoing' => false,
        'status' => 'received',
    ]);
    DuplicateChatToTicketService::duplicateMessage($msg2);

    expect(Ticket::where('chat_session_id', $this->session->id)->count())->toBe(1);
    expect($this->session->ticket->messages)->toHaveCount(2);
});
