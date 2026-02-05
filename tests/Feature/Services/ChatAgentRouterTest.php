<?php

use App\Events\SessionAssignedEvent;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;
use App\Services\ChatAgentRouter;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->router = new ChatAgentRouter;
    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);
});

it('finds agent with lowest workload', function () {
    $agent1 = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
    ]);

    $agent2 = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
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
        'assigned_to' => $agent1->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent2->id,
        'status' => 'open',
    ]);

    $bestAgent = $this->router->findBestAgent($this->customer->id);

    expect($bestAgent->id)->toBe($agent2->id);
});

it('returns null when no agents are available', function () {
    User::factory()->create([
        'role' => 'agent',
        'status' => 'offline',
        'is_active' => true,
    ]);

    $bestAgent = $this->router->findBestAgent($this->customer->id);

    expect($bestAgent)->toBeNull();
});

it('filters agents by skill when intent is provided', function () {
    $agent1 = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
        'skills' => ['billing', 'support'],
    ]);

    $agent2 = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
        'skills' => ['support', 'technical'],
    ]);

    $agent3 = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
        'skills' => ['sales'],
    ]);

    $bestAgent = $this->router->findBestAgent($this->customer->id, 'billing');

    expect($bestAgent->id)->toBe($agent1->id);
});

it('falls back to any available agent when no skill match found', function () {
    $agent1 = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
        'skills' => ['support'],
    ]);

    $bestAgent = $this->router->findBestAgent($this->customer->id, 'billing');

    expect($bestAgent->id)->toBe($agent1->id);
});

it('assigns session to available agent', function () {
    Event::fake([SessionAssignedEvent::class]);

    $agent = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
    ]);

    $session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    $result = $this->router->assignSession($session);

    expect($result->assigned_to)->toBe($agent->id);
    expect($result->status)->toBe('open');

    Event::assertDispatched(SessionAssignedEvent::class);
});

it('sets session to pending when no agents available', function () {
    Event::fake([SessionAssignedEvent::class]);

    $session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'bot',
    ]);

    $result = $this->router->assignSession($session);

    expect($result->assigned_to)->toBeNull();
    expect($result->status)->toBe('pending');

    Event::assertDispatched(SessionAssignedEvent::class);
});

it('assigns pending sessions to agent respecting workload limit', function () {
    Event::fake([SessionAssignedEvent::class]);

    $agent = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    $this->router->assignPendingTo($agent);

    $assignedCount = ChatSession::where('assigned_to', $agent->id)
        ->where('status', 'open')
        ->count();

    expect($assignedCount)->toBe(5);

    $pendingCount = ChatSession::where('status', 'pending')
        ->whereNull('assigned_to')
        ->count();

    expect($pendingCount)->toBe(2);
});

it('assigns all pending sessions when agent has no workload', function () {
    Event::fake([SessionAssignedEvent::class]);

    $agent = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    $this->router->assignPendingTo($agent);

    $assignedCount = ChatSession::where('assigned_to', $agent->id)
        ->where('status', 'open')
        ->count();

    expect($assignedCount)->toBe(3);

    $pendingCount = ChatSession::where('status', 'pending')
        ->whereNull('assigned_to')
        ->count();

    expect($pendingCount)->toBe(0);
});

it('assigns oldest pending sessions first', function () {
    Event::fake([SessionAssignedEvent::class]);

    $agent = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
    ]);

    $oldestSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
        'created_at' => now()->subHours(2),
    ]);

    $newestSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => null,
        'status' => 'pending',
        'created_at' => now()->subMinutes(10),
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    $this->router->assignPendingTo($agent);

    $oldestSession->refresh();
    $newestSession->refresh();

    expect($oldestSession->assigned_to)->toBe($agent->id);
    expect($oldestSession->status)->toBe('open');
    expect($newestSession->assigned_to)->toBeNull();
    expect($newestSession->status)->toBe('pending');
});

it('only counts open sessions in workload calculation', function () {
    $agent = User::factory()->agent()->create([
        'status' => 'online',
        'is_active' => true,
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'closed',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'closed',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'closed',
    ]);

    ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $agent->id,
        'status' => 'pending',
    ]);

    $bestAgent = $this->router->findBestAgent($this->customer->id);

    expect($bestAgent->id)->toBe($agent->id);
    expect($bestAgent->workload)->toBe(2);
});
