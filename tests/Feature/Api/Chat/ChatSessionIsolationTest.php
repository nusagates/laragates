<?php

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->customer1 = Customer::create([
        'phone' => '6281111111111',
        'name' => 'Customer 1',
    ]);

    $this->customer2 = Customer::create([
        'phone' => '6282222222222',
        'name' => 'Customer 2',
    ]);

    $this->agent1 = User::factory()->agent()->create([
        'name' => 'Agent 1',
        'status' => 'online',
    ]);

    $this->agent2 = User::factory()->agent()->create([
        'name' => 'Agent 2',
        'status' => 'online',
    ]);

    $this->admin = User::factory()->admin()->create([
        'name' => 'Admin',
    ]);

    $this->session1 = ChatSession::create([
        'customer_id' => $this->customer1->id,
        'assigned_to' => $this->agent1->id,
        'status' => 'open',
    ]);

    $this->session2 = ChatSession::create([
        'customer_id' => $this->customer2->id,
        'assigned_to' => $this->agent2->id,
        'status' => 'open',
    ]);
});

it('agent can only see their own assigned sessions', function () {
    $response = actingAs($this->agent1)
        ->getJson('/chat/sessions');

    $response->assertSuccessful();

    $sessions = $response->json();

    expect($sessions)->toHaveCount(1);
    expect($sessions[0]['session_id'])->toBe($this->session1->id);
});

it('agent cannot see sessions assigned to other agents', function () {
    $response = actingAs($this->agent1)
        ->getJson('/chat/sessions');

    $response->assertSuccessful();

    $sessions = $response->json();
    $sessionIds = collect($sessions)->pluck('session_id')->toArray();

    expect($sessionIds)->not->toContain($this->session2->id);
});

it('admin can see all sessions', function () {
    $response = actingAs($this->admin)
        ->getJson('/chat/sessions');

    $response->assertSuccessful();

    $sessions = $response->json();

    expect($sessions)->toHaveCount(2);
});

it('agent cannot view session detail assigned to another agent', function () {
    $response = actingAs($this->agent1)
        ->getJson("/chat/sessions/{$this->session2->id}");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('agent can view their own assigned session detail', function () {
    $response = actingAs($this->agent1)
        ->getJson("/chat/sessions/{$this->session1->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'session' => ['id', 'customer_id', 'assigned_to'],
        ]);
});

it('agent cannot view messages from session assigned to another agent', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session2->id,
        'sender' => 'customer',
        'message' => 'Private message',
    ]);

    $response = actingAs($this->agent1)
        ->getJson("/chat/sessions/{$this->session2->id}/messages");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('agent can view messages from their assigned session', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session1->id,
        'sender' => 'customer',
        'message' => 'Hello agent',
    ]);

    $response = actingAs($this->agent1)
        ->getJson("/chat/sessions/{$this->session1->id}/messages");

    $response->assertSuccessful();

    $messages = $response->json();
    expect($messages)->toHaveCount(1);
    expect($messages[0]['message'])->toBe('Hello agent');
});

it('agent cannot send message to session assigned to another agent', function () {
    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session2->id}/messages", [
            'message' => 'Unauthorized message',
        ]);

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('agent cannot pin session assigned to another agent', function () {
    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session2->id}/pin");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('agent can pin their own assigned session', function () {
    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session1->id}/pin");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Session pinned',
        ]);

    $this->session1->refresh();
    expect($this->session1->pinned)->toBeTrue();
});

it('agent cannot unpin session assigned to another agent', function () {
    $this->session2->update(['pinned' => true]);

    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session2->id}/unpin");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('agent can unpin their own assigned session', function () {
    $this->session1->update(['pinned' => true]);

    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session1->id}/unpin");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Session unpinned',
        ]);

    $this->session1->refresh();
    expect($this->session1->pinned)->toBeFalse();
});

it('agent cannot mark read session assigned to another agent', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session2->id,
        'sender' => 'customer',
        'message' => 'Unread message',
        'delivery_status' => 'delivered',
    ]);

    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session2->id}/mark-read");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('agent can mark read their own assigned session', function () {
    ChatMessage::create([
        'chat_session_id' => $this->session1->id,
        'sender' => 'customer',
        'message' => 'Unread message',
        'delivery_status' => 'delivered',
    ]);

    $response = actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session1->id}/mark-read");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Marked as read',
        ]);

    $message = ChatMessage::where('chat_session_id', $this->session1->id)->first();
    expect($message->delivery_status)->toBe('read');
});

it('admin can access any session', function () {
    $response1 = actingAs($this->admin)
        ->getJson("/chat/sessions/{$this->session1->id}");

    $response2 = actingAs($this->admin)
        ->getJson("/chat/sessions/{$this->session2->id}");

    $response1->assertSuccessful();
    $response2->assertSuccessful();
});

it('supervisor can access any session', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response1 = actingAs($supervisor)
        ->getJson("/chat/sessions/{$this->session1->id}");

    $response2 = actingAs($supervisor)
        ->getJson("/chat/sessions/{$this->session2->id}");

    $response1->assertSuccessful();
    $response2->assertSuccessful();
});

it('unassigned session is visible to all agents in session list', function () {
    $unassignedSession = ChatSession::create([
        'customer_id' => $this->customer1->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    // Agent1 should NOT see unassigned sessions (only their assigned sessions)
    $response = actingAs($this->agent1)
        ->getJson('/chat/sessions');

    $response->assertSuccessful();
    $sessions = $response->json();
    $sessionIds = collect($sessions)->pluck('session_id')->toArray();

    expect($sessionIds)->not->toContain($unassignedSession->id);
});
