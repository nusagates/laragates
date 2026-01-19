<?php

use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $this->agent1 = User::factory()->create(['role' => 'agent', 'is_online' => true]);
    $this->agent2 = User::factory()->create(['role' => 'agent', 'is_online' => true]);
    $this->agent3 = User::factory()->create(['role' => 'agent', 'is_online' => false]);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->nonAgent = User::factory()->create(['role' => 'supervisor']);

    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $this->session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent1->id,
        'status' => 'open',
    ]);
});

it('allows agent to reassign their session to another agent', function () {
    $response = $this->actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => $this->agent2->id,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Session reassigned successfully',
        ]);

    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'assigned_to' => $this->agent2->id,
        'status' => 'open',
    ]);

    // Verify system message was created
    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'type' => 'text',
    ]);
});

it('prevents agent from reassigning session not assigned to them', function () {
    $response = $this->actingAs($this->agent2)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => $this->agent2->id,
        ]);

    $response->assertForbidden()
        ->assertJson([
            'error' => 'Unauthorized. This session is not assigned to you.',
        ]);

    // Session should remain assigned to agent1
    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'assigned_to' => $this->agent1->id,
    ]);
});

it('allows admin to reassign any session', function () {
    $response = $this->actingAs($this->admin)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => $this->agent2->id,
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('chat_sessions', [
        'id' => $this->session->id,
        'assigned_to' => $this->agent2->id,
    ]);
});

it('prevents reassigning to same agent', function () {
    $response = $this->actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => $this->agent1->id,
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Session is already assigned to this agent.',
        ]);
});

it('prevents reassigning to non-agent user', function () {
    $response = $this->actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => $this->nonAgent->id,
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Selected user is not an agent.',
        ]);
});

it('requires agent_id parameter', function () {
    $response = $this->actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", []);

    $response->assertStatus(422);
});

it('validates agent_id exists in database', function () {
    $response = $this->actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => 99999,
        ]);

    $response->assertStatus(422);
});

it('creates system message with correct format', function () {
    $this->actingAs($this->agent1)
        ->postJson("/chat/sessions/{$this->session->id}/reassign", [
            'agent_id' => $this->agent2->id,
        ]);

    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $this->session->id,
        'sender' => 'system',
        'message' => sprintf(
            'Chat reassigned from %s to %s by %s',
            $this->agent1->name,
            $this->agent2->name,
            $this->agent1->name
        ),
    ]);
});

it('returns available agents list', function () {
    $response = $this->actingAs($this->agent1)
        ->getJson('/chat/agents/available');

    $response->assertOk()
        ->assertJsonStructure([
            'agents' => [
                '*' => ['id', 'name', 'email', 'sessions_count'],
            ],
        ]);

    // Only online agents should be returned
    $agents = $response->json('agents');
    expect($agents)->toHaveCount(2); // agent1 and agent2 (agent3 is offline)
});

it('sorts agents by workload (sessions count)', function () {
    // Create more sessions for agent1
    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent1->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent1->id,
        'status' => 'open',
    ]);

    $response = $this->actingAs($this->agent1)
        ->getJson('/chat/agents/available');

    $agents = $response->json('agents');

    // agent2 should be first (less sessions)
    expect($agents[0]['id'])->toBe($this->agent2->id);
    expect($agents[1]['id'])->toBe($this->agent1->id);
    expect($agents[1]['sessions_count'])->toBeGreaterThan($agents[0]['sessions_count']);
});
