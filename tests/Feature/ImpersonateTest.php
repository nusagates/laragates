<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

// ========================================
// AUTHORIZATION TESTS
// ========================================

test('superadmin can impersonate agent', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();

    $response = actingAs($superadmin)->postJson("/agents/{$agent->id}/impersonate");

    $response->assertSuccessful();
    expect(session()->get('impersonate_original_user'))->toBe($superadmin->id);
});

test('superadmin can impersonate supervisor', function () {
    $superadmin = User::factory()->superadmin()->create();
    $supervisor = User::factory()->supervisor()->create();

    $response = actingAs($superadmin)->postJson("/agents/{$supervisor->id}/impersonate");

    $response->assertSuccessful();
    expect(session()->get('impersonate_original_user'))->toBe($superadmin->id);
});

test('superadmin can impersonate admin', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create();

    $response = actingAs($superadmin)->postJson("/agents/{$admin->id}/impersonate");

    $response->assertSuccessful();
    expect(session()->get('impersonate_original_user'))->toBe($superadmin->id);
});

test('admin can impersonate agent', function () {
    $admin = User::factory()->admin()->create();
    $agent = User::factory()->agent()->create();

    $response = actingAs($admin)->postJson("/agents/{$agent->id}/impersonate");

    $response->assertSuccessful();
    expect(session()->get('impersonate_original_user'))->toBe($admin->id);
});

test('admin can impersonate supervisor', function () {
    $admin = User::factory()->admin()->create();
    $supervisor = User::factory()->supervisor()->create();

    $response = actingAs($admin)->postJson("/agents/{$supervisor->id}/impersonate");

    $response->assertSuccessful();
    expect(session()->get('impersonate_original_user'))->toBe($admin->id);
});

test('admin cannot impersonate another admin', function () {
    $admin = User::factory()->admin()->create();
    $anotherAdmin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson("/agents/{$anotherAdmin->id}/impersonate");

    $response->assertForbidden();
});

test('admin cannot impersonate superadmin', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();

    $response = actingAs($admin)->postJson("/agents/{$superadmin->id}/impersonate");

    $response->assertForbidden();
});

test('supervisor cannot impersonate anyone', function () {
    $supervisor = User::factory()->supervisor()->create();
    $agent = User::factory()->agent()->create();

    $response = actingAs($supervisor)->postJson("/agents/{$agent->id}/impersonate");

    $response->assertForbidden();
});

test('agent cannot impersonate anyone', function () {
    $agent = User::factory()->agent()->create();
    $anotherAgent = User::factory()->agent()->create();

    $response = actingAs($agent)->postJson("/agents/{$anotherAgent->id}/impersonate");

    $response->assertForbidden();
});

// ========================================
// EDGE CASE TESTS
// ========================================

test('user cannot impersonate themselves', function () {
    $superadmin = User::factory()->superadmin()->create();

    $response = actingAs($superadmin)->postJson("/agents/{$superadmin->id}/impersonate");

    $response->assertForbidden();
});

test('cannot impersonate when already impersonating', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();
    $supervisor = User::factory()->supervisor()->create();

    // Start first impersonation
    session()->put('impersonate_original_user', $superadmin->id);

    $response = actingAs($agent)->postJson("/agents/{$supervisor->id}/impersonate");

    $response->assertForbidden();
    expect($response->json('message'))->toContain('already impersonating');
});

// ========================================
// LEAVE IMPERSONATION TESTS
// ========================================

test('can leave impersonation', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();

    // Start impersonation
    session()->put('impersonate_original_user', $superadmin->id);
    auth()->login($agent);

    $response = actingAs($agent)->postJson('/impersonate/leave');

    $response->assertSuccessful();
    expect(session()->has('impersonate_original_user'))->toBeFalse();
});

test('cannot leave impersonation when not impersonating', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/impersonate/leave');

    $response->assertStatus(400);
    expect($response->json('message'))->toContain('not impersonating');
});

// ========================================
// AUDIT LOG TESTS
// ========================================

test('impersonation start is logged in iam_logs', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();

    actingAs($superadmin)->postJson("/agents/{$agent->id}/impersonate");

    assertDatabaseHas('iam_logs', [
        'action' => 'IMPERSONATE_START',
        'target_user_id' => $agent->id,
    ]);
});

test('impersonation leave is logged in iam_logs', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();

    // Start impersonation
    session()->put('impersonate_original_user', $superadmin->id);
    auth()->login($agent);

    actingAs($agent)->postJson('/impersonate/leave');

    assertDatabaseHas('iam_logs', [
        'action' => 'IMPERSONATE_LEAVE',
        'target_user_id' => $agent->id,
    ]);
});

// ========================================
// INTEGRATION TESTS
// ========================================

test('impersonated user has correct identity', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();

    actingAs($superadmin)->postJson("/agents/{$agent->id}/impersonate");

    expect(auth()->id())->toBe($agent->id);
    expect(auth()->user()->role)->toBe('agent');
});

test('after leaving impersonation user returns to original identity', function () {
    $superadmin = User::factory()->superadmin()->create();
    $agent = User::factory()->agent()->create();

    // Start impersonation
    session()->put('impersonate_original_user', $superadmin->id);
    auth()->login($agent);

    actingAs($agent)->postJson('/impersonate/leave');

    expect(auth()->id())->toBe($superadmin->id);
    expect(auth()->user()->role)->toBe('superadmin');
});
