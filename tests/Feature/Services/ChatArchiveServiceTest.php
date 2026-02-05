<?php

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\ChatSessionArchive;
use App\Models\Customer;
use App\Models\User;
use App\Services\ChatArchiveService;
use Carbon\Carbon;

beforeEach(function () {
    $this->archiveService = app(ChatArchiveService::class);

    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $this->agent = User::factory()->create(['role' => 'agent']);
});

it('archives sessions closed for more than 7 days', function () {
    // Create old closed session (8 days ago)
    $oldSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'closed',
        'closed_at' => Carbon::now()->subDays(8),
        'created_at' => Carbon::now()->subDays(10),
        'updated_at' => Carbon::now()->subDays(8),
    ]);

    ChatMessage::create([
        'chat_session_id' => $oldSession->id,
        'customer_id' => $this->customer->id,
        'sender' => 'customer',
        'message' => 'Hello',
        'type' => 'text',
    ]);

    // Create recent closed session (3 days ago)
    $recentSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'closed',
        'closed_at' => Carbon::now()->subDays(3),
        'created_at' => Carbon::now()->subDays(4),
        'updated_at' => Carbon::now()->subDays(3),
    ]);

    $archivedCount = $this->archiveService->archiveOldClosedSessions();

    expect($archivedCount)->toBe(1);

    $this->assertDatabaseMissing('chat_sessions', ['id' => $oldSession->id]);
    $this->assertDatabaseHas('chat_sessions_archive', [
        'customer_id' => $this->customer->id,
        'status' => 'archived',
    ]);

    $this->assertDatabaseHas('chat_sessions', ['id' => $recentSession->id]);
});

it('does not archive open sessions', function () {
    $openSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'open',
        'updated_at' => Carbon::now()->subDays(10),
    ]);

    $archivedCount = $this->archiveService->archiveOldClosedSessions();

    expect($archivedCount)->toBe(0);
    $this->assertDatabaseHas('chat_sessions', ['id' => $openSession->id]);
});

it('copies all messages to archive', function () {
    $oldSession = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'closed',
        'closed_at' => Carbon::now()->subDays(8),
        'created_at' => Carbon::now()->subDays(10),
        'updated_at' => Carbon::now()->subDays(8),
    ]);

    ChatMessage::create([
        'chat_session_id' => $oldSession->id,
        'customer_id' => $this->customer->id,
        'sender' => 'customer',
        'message' => 'Message 1',
        'type' => 'text',
    ]);

    ChatMessage::create([
        'chat_session_id' => $oldSession->id,
        'customer_id' => $this->customer->id,
        'sender' => 'agent',
        'message' => 'Message 2',
        'type' => 'text',
    ]);

    $this->archiveService->archiveOldClosedSessions();

    // Verify messages were archived
    $archivedSession = ChatSessionArchive::first();
    expect($archivedSession->messages)->toHaveCount(2);

    // Verify original messages were deleted
    $this->assertDatabaseMissing('chat_messages', [
        'chat_session_id' => $oldSession->id,
    ]);
});
