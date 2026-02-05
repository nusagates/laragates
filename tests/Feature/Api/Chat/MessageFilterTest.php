<?php

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->customer = Customer::create([
        'phone' => '6281111111111',
        'name' => 'Test Customer',
    ]);

    $this->agent = User::factory()->agent()->create([
        'name' => 'Test Agent',
        'status' => 'online',
    ]);

    $this->session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'open',
    ]);
});

it('filters out messages containing "Sent via fonnte.com"', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello agent',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Message sent Sent via fonnte.com',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'agent',
        'message' => 'Hi there',
    ]);

    $response = actingAs($this->agent)->getJson("/chat/sessions/{$this->session->id}/messages");

    $response->assertSuccessful();
    $messages = $response->json();

    // Should have 2 messages (excluding the fonnte message)
    expect($messages)->toHaveCount(2);
    expect($messages[0]['message'])->toBe('Hello agent');
    expect($messages[1]['message'])->toBe('Hi there');
});

it('filters out messages containing "Message queued"', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Message queued for delivery',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'agent',
        'message' => 'Response here',
    ]);

    $response = actingAs($this->agent)->getJson("/chat/sessions/{$this->session->id}/messages");

    $response->assertSuccessful();
    $messages = $response->json();

    // Should have 2 messages (excluding the queued message)
    expect($messages)->toHaveCount(2);
    expect($messages[0]['message'])->toBe('Hello');
    expect($messages[1]['message'])->toBe('Response here');
});

it('excludes both types of messages', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Message 1',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Sent via fonnte.com',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'agent',
        'message' => 'Message 2',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Message queued',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Message 3',
    ]);

    $response = actingAs($this->agent)->getJson("/chat/sessions/{$this->session->id}/messages");

    $response->assertSuccessful();
    $messages = $response->json();

    // Should have 3 messages (5 total - 2 filtered)
    expect($messages)->toHaveCount(3);
    expect($messages[0]['message'])->toBe('Message 1');
    expect($messages[1]['message'])->toBe('Message 2');
    expect($messages[2]['message'])->toBe('Message 3');
});

it('still shows normal system messages', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Chat session closed by Admin',
    ]);

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Chat reassigned from Agent 1 to Agent 2',
    ]);

    $response = actingAs($this->agent)->getJson("/chat/sessions/{$this->session->id}/messages");

    $response->assertSuccessful();
    $messages = $response->json();

    // Should have all 3 messages
    expect($messages)->toHaveCount(3);
    expect($messages[0]['message'])->toBe('Hello');
    expect($messages[1]['message'])->toBe('Chat session closed by Admin');
    expect($messages[2]['message'])->toBe('Chat reassigned from Agent 1 to Agent 2');
});
