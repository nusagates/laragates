<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

// ========================================
// AUTHORIZATION TESTS
// ========================================

test('superadmin can access user management', function () {
    $superadmin = User::factory()->superadmin()->create();

    $response = actingAs($superadmin)->get('/users');

    $response->assertSuccessful();
});

test('admin can access user management', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->get('/users');

    $response->assertSuccessful();
});

test('agent cannot access user management', function () {
    $agent = User::factory()->agent()->create();

    $response = actingAs($agent)->get('/users');

    $response->assertForbidden();
});

test('supervisor cannot access user management', function () {
    $supervisor = User::factory()->supervisor()->create();

    $response = actingAs($supervisor)->get('/users');

    $response->assertForbidden();
});

// ========================================
// CRUD TESTS - CREATE
// ========================================

test('admin can create new user with agent role', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/users', [
        'name' => 'Test Agent',
        'email' => 'agent@example.com',
        'role' => 'Agent',
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'email' => 'agent@example.com',
        'role' => 'agent',
    ]);
});

test('admin can create new user with superadmin role', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/users', [
        'name' => 'Test Superadmin',
        'email' => 'super@example.com',
        'role' => 'Superadmin',
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'email' => 'super@example.com',
        'role' => 'superadmin',
    ]);
});

test('creating user returns temporary password', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'Agent',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['temp_password', 'user']);
    expect($response->json('temp_password'))->toBeString();
});

// ========================================
// CRUD TESTS - READ/INDEX
// ========================================

test('user index includes all roles', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->agent()->create(['name' => 'Agent User']);
    User::factory()->supervisor()->create(['name' => 'Supervisor User']);
    User::factory()->superadmin()->create(['name' => 'Superadmin User']);

    $response = actingAs($admin)->get('/users');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users', 4)
    );
});

test('user index includes locked status', function () {
    $admin = User::factory()->admin()->create();
    $lockedUser = User::factory()->agent()->locked()->create();

    $response = actingAs($admin)->get('/users');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('users', fn ($users) => collect($users)->contains('is_locked', true))
    );
});

// ========================================
// CRUD TESTS - UPDATE
// ========================================

test('admin can update user details', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create(['name' => 'Old Name']);

    $response = actingAs($admin)->putJson("/users/{$user->id}", [
        'name' => 'New Name',
        'email' => $user->email,
        'role' => 'Agent',
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);
});

test('admin can change user role to supervisor', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    $response = actingAs($admin)->putJson("/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'Supervisor',
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'supervisor',
    ]);
});

test('admin can change user role to superadmin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    $response = actingAs($admin)->putJson("/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'Superadmin',
    ]);

    $response->assertSuccessful();
    assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'superadmin',
    ]);
});

// ========================================
// CRUD TESTS - DELETE
// ========================================

test('admin can delete user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    $response = actingAs($admin)->deleteJson("/users/{$user->id}");

    $response->assertSuccessful();
    assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

// ========================================
// EDGE CASE TESTS
// ========================================

test('user cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->deleteJson("/users/{$admin->id}");

    $response->assertStatus(422);
    assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

test('user cannot change their own role', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->putJson("/users/{$admin->id}", [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'Agent',
    ]);

    $response->assertStatus(422);
    assertDatabaseHas('users', [
        'id' => $admin->id,
        'role' => 'admin',
    ]);
});

test('user cannot reset their own password via admin endpoint', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson("/users/{$admin->id}/reset-password");

    $response->assertStatus(422);
});

// ========================================
// VALIDATION TESTS
// ========================================

test('email must be unique when creating user', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->create(['email' => 'existing@example.com']);

    $response = actingAs($admin)->postJson('/users', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'role' => 'Agent',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

test('role must be valid when creating user', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'InvalidRole',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('role');
});

test('name is required when creating user', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/users', [
        'email' => 'test@example.com',
        'role' => 'Agent',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('name');
});

test('email is required when creating user', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/users', [
        'name' => 'Test User',
        'role' => 'Agent',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('email');
});

// ========================================
// PASSWORD RESET TESTS
// ========================================

test('admin can reset user password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    $response = actingAs($admin)->postJson("/users/{$user->id}/reset-password");

    $response->assertSuccessful();
    $response->assertJsonStructure(['temp_password']);
    expect($response->json('temp_password'))->toBeString();
});

test('password reset generates new password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();
    $oldPasswordHash = $user->password;

    actingAs($admin)->postJson("/users/{$user->id}/reset-password");

    $user->refresh();
    expect($user->password)->not->toBe($oldPasswordHash);
});

// ========================================
// ACCOUNT LOCK/UNLOCK TESTS
// ========================================

test('admin can unlock locked user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->locked()->create();

    $response = actingAs($admin)->postJson("/users/{$user->id}/unlock");

    $response->assertSuccessful();
    $user->refresh();
    expect($user->locked_until)->toBeNull();
    expect($user->failed_login_attempts)->toBe(0);
});

// ========================================
// APPROVAL TESTS
// ========================================

test('admin can approve pending user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create([
        'approved_at' => null,
        'status' => 'pending',
    ]);

    $response = actingAs($admin)->postJson("/users/{$user->id}/approve");

    $response->assertSuccessful();
    $user->refresh();
    expect($user->approved_at)->not->toBeNull();
});

// ========================================
// IAM LOGGING TESTS
// ========================================

test('user creation is logged in iam_logs', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)->postJson('/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'Agent',
    ]);

    assertDatabaseHas('iam_logs', [
        'action' => 'CREATE_USER',
        'actor_id' => $admin->id,
    ]);
});

test('user update is logged in iam_logs', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    actingAs($admin)->putJson("/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => $user->email,
        'role' => 'Supervisor',
    ]);

    assertDatabaseHas('iam_logs', [
        'action' => 'UPDATE_USER',
        'actor_id' => $admin->id,
        'target_user_id' => $user->id,
    ]);
});

test('user deletion is logged in iam_logs', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    actingAs($admin)->deleteJson("/users/{$user->id}");

    assertDatabaseHas('iam_logs', [
        'action' => 'DELETE_USER',
        'actor_id' => $admin->id,
        'target_user_id' => $user->id,
    ]);
});

test('password reset is logged in iam_logs', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create();

    actingAs($admin)->postJson("/users/{$user->id}/reset-password");

    assertDatabaseHas('iam_logs', [
        'action' => 'RESET_PASSWORD',
        'actor_id' => $admin->id,
        'target_user_id' => $user->id,
    ]);
});

test('user approval is logged in iam_logs', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->agent()->create(['approved_at' => null]);

    actingAs($admin)->postJson("/users/{$user->id}/approve");

    assertDatabaseHas('iam_logs', [
        'action' => 'APPROVE_USER',
        'actor_id' => $admin->id,
        'target_user_id' => $user->id,
    ]);
});
