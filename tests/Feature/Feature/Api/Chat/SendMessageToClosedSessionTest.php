<?php

use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $this->agent = User::factory()->create([
        'role' => 'agent',
        'approved_at' => now(),
    ]);
    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $this->closedSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'closed',
    ]);

    $this->openSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'open',
    ]);
});

it('prevents sending message to closed session', function () {
    $response = $this->actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->closedSession->id}/messages", [
            'message' => 'Hello',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Cannot send message to a closed session.',
            'status' => 'closed',
        ]);

    $this->assertDatabaseMissing('chat_messages', [
        'chat_session_id' => $this->closedSession->id,
        'message' => 'Hello',
    ]);
});

it('allows sending message to open session', function () {
    $response = $this->actingAs($this->agent)
        ->postJson("/chat/sessions/{$this->openSession->id}/messages", [
            'message' => 'Hello',
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('chat_messages', [
        'chat_session_id' => $this->openSession->id,
        'message' => 'Hello',
    ]);
});
