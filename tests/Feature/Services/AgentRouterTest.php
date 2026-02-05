<?php

use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;
use App\Services\AgentRouter;

beforeEach(function () {
    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);
});

it('assigns agent with least active chats', function () {
    $agent1 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $agent2 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent1->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent1->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent2->id,
        'status' => 'open',
    ]);

    $assignedAgentId = AgentRouter::assign();

    expect($assignedAgentId)->toBe($agent2->id);
});

it('returns null when no agents are available', function () {
    User::factory()->agent()->create([
        'is_online' => false,
        'last_heartbeat_at' => now()->subMinutes(2),
    ]);

    $assignedAgentId = AgentRouter::assign();

    expect($assignedAgentId)->toBeNull();
});

it('only assigns agents with recent heartbeat', function () {
    $onlineAgent = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $staleAgent = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now()->subMinutes(2),
    ]);

    $assignedAgentId = AgentRouter::assign();

    expect($assignedAgentId)->toBe($onlineAgent->id);
});

it('prefers agent with most recent heartbeat when workload is equal', function () {
    $agent1 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now()->subSeconds(30),
    ]);

    $agent2 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $assignedAgentId = AgentRouter::assign();

    expect($assignedAgentId)->toBe($agent2->id);
});

it('assigns agent to session successfully', function () {
    $agent = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'bot',
    ]);

    $assignedAgentId = AgentRouter::assignToSession($session);

    expect($assignedAgentId)->toBe($agent->id);

    $session->refresh();

    expect($session->assigned_to)->toBe($agent->id);
    expect($session->status)->toBe('pending');
});

it('returns existing agent if session already assigned', function () {
    $agent1 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $agent2 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent1->id,
        'status' => 'pending',
    ]);

    $assignedAgentId = AgentRouter::assignToSession($session);

    expect($assignedAgentId)->toBe($agent1->id);

    $session->refresh();

    expect($session->assigned_to)->toBe($agent1->id);
});

it('returns null when no agents available for session assignment', function () {
    $session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'bot',
    ]);

    $assignedAgentId = AgentRouter::assignToSession($session);

    expect($assignedAgentId)->toBeNull();

    $session->refresh();

    expect($session->assigned_to)->toBeNull();
});

it('counts pending and open chats in workload calculation', function () {
    $agent1 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    $agent2 = User::factory()->agent()->create([
        'is_online' => true,
        'last_heartbeat_at' => now(),
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent1->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent1->id,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent1->id,
        'status' => 'closed',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent2->id,
        'status' => 'open',
    ]);

    $assignedAgentId = AgentRouter::assign();

    expect($assignedAgentId)->toBe($agent2->id);
});
