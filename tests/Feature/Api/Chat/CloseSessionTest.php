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

    ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello agent',
    ]);
});

it('agent can close their assigned session', function () {
    $response = actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Session closed successfully',
        ]);

    // Verify session status is updated to closed
    $this->session->refresh();
    expect($this->session->status)->toBe('closed');
    expect($this->session->closed_at)->not->toBeNull();
});

it('returns updated session data when closed', function () {
    $response = actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'session' => [
                'id',
                'status',
                'customer_id',
                'assigned_to',
                'closed_at',
            ],
        ]);

    expect($response->json('session.status'))->toBe('closed');
});

it('system message is created when session is closed', function () {
    $messageCountBefore = ChatMessage::where('chat_session_id', $this->session->id)->count();

    actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $messageCountAfter = ChatMessage::where('chat_session_id', $this->session->id)->count();

    expect($messageCountAfter)->toBe($messageCountBefore + 1);

    $systemMessage = ChatMessage::where('chat_session_id', $this->session->id)
        ->where('sender', 'system')
        ->latest()
        ->first();

    expect($systemMessage)->not->toBeNull();
    expect($systemMessage->message)->toContain('Chat session closed');
});

it('agent cannot close session already closed', function () {
    $this->session->update(['status' => 'closed']);

    $response = actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Session is already closed.',
        ]);
});

it('agent cannot close session assigned to another agent', function () {
    $otherAgent = User::factory()->agent()->create([
        'name' => 'Other Agent',
    ]);

    $response = actingAs($otherAgent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);
});

it('admin can close any session', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertSuccessful();

    $this->session->refresh();
    expect($this->session->status)->toBe('closed');
});

it('closed session does not accept new messages', function () {
    actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response = actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/messages", [
            'message' => 'This should fail',
        ]);

    $response->assertStatus(400)
        ->assertJsonStructure(['error', 'status'])
        ->assertJson(['status' => 'closed']);
});

it('closed session is still visible in session list by assigned agent', function () {
    actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response = actingAs($this->agent)
        ->getJson('/chat/sessions');

    $response->assertSuccessful();

    $sessions = $response->json();
    $closedSession = collect($sessions)
        ->first(fn ($s) => $s['session_id'] === $this->session->id);

    expect($closedSession)->not->toBeNull();
    expect($closedSession['status'])->toBe('closed');
});
