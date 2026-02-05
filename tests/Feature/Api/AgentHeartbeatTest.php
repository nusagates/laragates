<?php

use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('updates agent heartbeat and status successfully', function () {
    $agent = User::factory()->agent()->create([
        'status' => 'offline',
        'is_online' => false,
    ]);

    actingAs($agent)
        ->postJson('/agent/heartbeat')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'message' => 'Heartbeat received',
        ]);

    $agent->refresh();

    expect($agent->is_online)->toBeTrue();
    expect($agent->status)->toBe('online');
    expect($agent->last_heartbeat_at)->not->toBeNull();
    expect($agent->last_seen)->not->toBeNull();
});

it('rejects heartbeat from non-agent users', function () {
    $user = User::factory()->create([
        'role' => 'admin',
    ]);

    actingAs($user)
        ->postJson('/agent/heartbeat')
        ->assertForbidden()
        ->assertJson([
            'message' => 'Unauthorized. Only agents can send heartbeat.',
        ]);
});

it('requires authentication', function () {
    postJson('/agent/heartbeat')
        ->assertUnauthorized(); // JSON requests return 401
});

it('assigns pending sessions when agent sends heartbeat', function () {
    $customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $agent = User::factory()->agent()->create([
        'status' => 'offline',
        'is_online' => false,
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    actingAs($agent)
        ->postJson('/agent/heartbeat')
        ->assertSuccessful();

    $assignedCount = ChatSession::where('assigned_to', $agent->id)
        ->where('status', 'open')
        ->count();

    expect($assignedCount)->toBe(3);
});

it('respects workload limit when assigning pending sessions', function () {
    $customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $agent = User::factory()->agent()->create([
        'status' => 'offline',
        'is_online' => false,
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'open',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => null,
        'status' => 'pending',
    ]);

    actingAs($agent)
        ->postJson('/agent/heartbeat')
        ->assertSuccessful();

    $totalAssigned = ChatSession::where('assigned_to', $agent->id)
        ->where('status', 'open')
        ->count();

    expect($totalAssigned)->toBe(5);

    $stillPending = ChatSession::where('status', 'pending')
        ->whereNull('assigned_to')
        ->count();

    expect($stillPending)->toBe(2);
});

it('updates last_seen timestamp on every heartbeat', function () {
    $agent = User::factory()->agent()->create([
        'last_seen' => now()->subMinutes(10),
    ]);

    $oldLastSeen = $agent->last_seen;

    sleep(1);

    actingAs($agent)
        ->postJson('/agent/heartbeat')
        ->assertSuccessful();

    $agent->refresh();

    expect($agent->last_seen->isAfter($oldLastSeen))->toBeTrue();
});

it('maintains online status for already online agent', function () {
    $agent = User::factory()->agent()->create([
        'status' => 'online',
        'is_online' => true,
    ]);

    actingAs($agent)
        ->postJson('/agent/heartbeat')
        ->assertSuccessful();

    $agent->refresh();

    expect($agent->is_online)->toBeTrue();
    expect($agent->status)->toBe('online');
});
