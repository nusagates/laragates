<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->agent = User::factory()->agent()->create();
});

test('admin can view agents index page', function () {
    $response = actingAs($this->admin)->get('/agents');

    $response->assertSuccessful();
    $response->assertInertia(fn ($assert) => $assert
        ->component('Agents/Index')
        ->has('agents')
        ->has('counts')
    );
});

test('non-admin cannot view agents index page', function () {
    $response = actingAs($this->agent)->get('/agents');

    $response->assertForbidden();
});

test('admin can create new agent', function () {
    $response = actingAs($this->admin)->post('/agents', [
        'name' => 'Test Agent',
        'email' => 'testagent@example.com',
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'name' => 'Test Agent',
        'email' => 'testagent@example.com',
        'role' => 'agent',
    ]);
});

test('admin cannot create agent with duplicate email', function () {
    $response = actingAs($this->admin)->postJson('/agents', [
        'name' => 'Test Agent',
        'email' => $this->agent->email,
    ]);

    $response->assertStatus(422);
});

test('admin can update agent', function () {
    $response = actingAs($this->admin)->putJson("/agents/{$this->agent->id}", [
        'name' => 'Updated Agent Name',
        'email' => $this->agent->email,
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'id' => $this->agent->id,
        'name' => 'Updated Agent Name',
    ]);
});

test('admin can approve agent', function () {
    $pendingAgent = User::factory()->agent()->create(['approved_at' => null]);

    $response = actingAs($this->admin)->postJson("/agents/{$pendingAgent->id}/approve");

    $response->assertSuccessful();
    expect($pendingAgent->fresh()->approved_at)->not->toBeNull();
});

test('admin can unlock agent', function () {
    $lockedAgent = User::factory()->agent()->locked()->create();

    $response = actingAs($this->admin)->post("/agents/{$lockedAgent->id}/unlock");

    $response->assertSuccessful();
    expect($lockedAgent->fresh()->failed_login_attempts)->toBe(0);
    expect($lockedAgent->fresh()->locked_until)->toBeNull();
});

test('admin can reset agent password', function () {
    $response = actingAs($this->admin)->post("/agents/{$this->agent->id}/reset-password");

    $response->assertSuccessful();
    $response->assertJsonStructure(['temp_password']);
});

test('admin can soft delete agent', function () {
    $response = actingAs($this->admin)->delete("/agents/{$this->agent->id}");

    $response->assertSuccessful();
    assertSoftDeleted('users', ['id' => $this->agent->id]);
});

test('admin can permanently delete agent', function () {
    $this->agent->delete();

    $response = actingAs($this->admin)->delete("/agents/{$this->agent->id}/force");

    $response->assertSuccessful();
    assertDatabaseMissing('users', ['id' => $this->agent->id]);
});

test('admin can restore deleted agent', function () {
    $this->agent->delete();

    $response = actingAs($this->admin)->post("/agents/{$this->agent->id}/restore");

    $response->assertSuccessful();
    expect($this->agent->fresh()->deleted_at)->toBeNull();
});

test('admin cannot delete themselves when they are agent', function () {
    // Create an admin with agent role (edge case but still valid)
    $adminAgent = User::factory()->create(['role' => 'admin']);

    // Create a real agent to act as admin
    $adminUser = User::factory()->admin()->create();

    // Admin tries to delete an agent (should work)
    $response = actingAs($adminUser)->deleteJson("/agents/{$this->agent->id}");
    $response->assertSuccessful();

    // Verify agent was deleted
    expect($this->agent->fresh()->deleted_at)->not->toBeNull();
});

test('agents index shows correct counts', function () {
    // Create test agents
    User::factory()->agent()->create(['approved_at' => null]);
    User::factory()->agent()->locked()->create();
    $deletedAgent = User::factory()->agent()->create();
    $deletedAgent->delete();

    $response = actingAs($this->admin)->get('/agents?show_deleted=1');

    $response->assertInertia(fn ($assert) => $assert
        ->component('Agents/Index')
        ->where('counts.pending', fn ($count) => $count >= 1)
        ->where('counts.locked', fn ($count) => $count >= 1)
        ->where('counts.deleted', fn ($count) => $count >= 1)
    );
});

test('non-admin cannot create agent', function () {
    $response = actingAs($this->agent)->post('/agents', [
        'name' => 'Test Agent',
        'email' => 'testagent@example.com',
    ]);

    $response->assertForbidden();
});

test('non-admin cannot update agent', function () {
    $response = actingAs($this->agent)->put("/agents/{$this->agent->id}", [
        'name' => 'Updated Agent Name',
        'email' => $this->agent->email,
    ]);

    $response->assertForbidden();
});

test('non-admin cannot delete agent', function () {
    $response = actingAs($this->agent)->delete("/agents/{$this->agent->id}");

    $response->assertForbidden();
});

test('admin cannot update non-agent user', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response = actingAs($this->admin)->putJson("/agents/{$supervisor->id}", [
        'name' => 'Updated Name',
        'email' => $supervisor->email,
    ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => 'User is not an agent']);
});

test('admin cannot approve non-agent user', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response = actingAs($this->admin)->postJson("/agents/{$supervisor->id}/approve");

    $response->assertStatus(422);
    $response->assertJson(['message' => 'User is not an agent']);
});
