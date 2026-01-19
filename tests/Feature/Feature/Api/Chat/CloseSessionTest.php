<?php

use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $this->agent = User::factory()->create(['role' => 'agent']);
    $this->otherAgent = User::factory()->create(['role' => 'agent']);
    $this->admin = User::factory()->create(['role' => 'admin']);

    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $this->session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'open',
    ]);
});

it('allows agent to close their assigned session', function () {
    $response = $this->actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Session closed successfully',
        ]);

    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'status' => 'closed',
        'is_handover' => false,
    ]);

    // Verify system message was created
    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'type' => 'text',
    ]);
});

it('prevents agent from closing session assigned to another agent', function () {
    $response = $this->actingAs($this->otherAgent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);

    // Session should remain open
    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'status' => 'open',
    ]);
});

it('allows admin to close any session', function () {
    $response = $this->actingAs($this->admin)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'status' => 'closed',
    ]);
});

it('prevents closing already closed session', function () {
    // First close
    $this->session->update(['status' => 'closed']);

    // Try to close again
    $response = $this->actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Session is already closed.',
        ]);
});

it('requires authentication', function () {
    $response = $this->postJson("/chat/sessions/{$this->session->id}/close");

    $response->assertUnauthorized();
});

it('creates system message with agent name when closing', function () {
    $this->actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => 'Chat session closed by '.$this->agent->name,
    ]);
});

it('sets is_handover to false when closing', function () {
    // Set session to handover mode
    $this->session->update(['is_handover' => true]);

    $this->actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->session->id}/close");

    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'status' => 'closed',
        'is_handover' => false,
    ]);
});
