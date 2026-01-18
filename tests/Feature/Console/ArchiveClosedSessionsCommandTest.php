<?php

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;

it('runs archive command successfully', function () {
    $customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $agent = User::factory()->create(['role' => 'agent']);

    $oldSession = ChatSession::create([
        'customer_id' => $customer->id,
        'assigned_to' => $agent->id,
        'status' => 'closed',
        'closed_at' => Carbon::now()->subDays(8),
        'updated_at' => Carbon::now()->subDays(8),
    ]);

    ChatMessage::create([
        'chat_session_id' => $oldSession->id,
        'customer_id' => $customer->id,
        'sender' => 'customer',
        'message' => 'Test message',
        'type' => 'text',
    ]);

    $this->artisan('chat:archive-closed-sessions')
        ->expectsOutput('Starting to archive old closed sessions...')
        ->assertSuccessful();

    $this->assertDatabaseMissing('chat_sessions', ['id' => $oldSession->id]);
    $this->assertDatabaseHas('chat_sessions_archive', [
        'customer_id' => $customer->id,
    ]);
});

it('reports correct count of archived sessions', function () {
    $customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $agent = User::factory()->create(['role' => 'agent']);

    // Create 3 old closed sessions
    for ($i = 0; $i < 3; $i++) {
        ChatSession::create([
            'customer_id' => $customer->id,
            'assigned_to' => $agent->id,
            'status' => 'closed',
            'closed_at' => Carbon::now()->subDays(8),
            'updated_at' => Carbon::now()->subDays(8),
        ]);
    }

    $this->artisan('chat:archive-closed-sessions')
        ->expectsOutput('Successfully archived 3 session(s).')
        ->assertSuccessful();
});
