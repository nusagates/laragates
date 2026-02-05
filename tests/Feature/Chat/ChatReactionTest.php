<?php

use App\Events\Chat\MessageUpdated;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->agent = User::factory()->create(['role' => 'agent']);

    $this->customer = Customer::create([
        'phone' => '6281234567890',
        'name' => 'Test Customer',
    ]);

    $this->session = ChatSession::create([
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->agent->id,
        'status' => 'open',
    ]);

    $this->message = ChatMessage::create([
        'chat_session_id' => $this->session->id,
        'sender' => 'customer',
        'message' => 'Hello!',
        'delivery_status' => 'delivered',
    ]);
});

it('can add a reaction to a message', function () {
    Event::fake();

    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/reaction", [
            'emoji' => '👍',
        ])
        ->assertSuccessful()
        ->assertJson([
            'status' => 'ok',
        ]);

    $this->message->refresh();
    expect($this->message->reactions)->toBe(['👍' => 1]);

    Event::assertDispatched(MessageUpdated::class);
});

it('increments reaction count when adding same emoji', function () {
    $this->message->update(['reactions' => ['👍' => 1]]);

    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/reaction", [
            'emoji' => '👍',
        ])
        ->assertSuccessful();

    $this->message->refresh();
    expect($this->message->reactions)->toBe(['👍' => 2]);
});

it('can add multiple different reactions', function () {
    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/reaction", ['emoji' => '👍'])
        ->assertSuccessful();

    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/reaction", ['emoji' => '❤️'])
        ->assertSuccessful();

    $this->message->refresh();
    expect($this->message->reactions)
        ->toHaveKey('👍')
        ->toHaveKey('❤️');
});

it('validates emoji is required', function () {
    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/reaction", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['emoji']);
});

it('can mark message as read', function () {
    Event::fake();

    $this->message->update(['delivery_status' => 'delivered']);

    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/mark-read")
        ->assertSuccessful();

    $this->message->refresh();
    expect($this->message->delivery_status)->toBe('read');

    Event::assertDispatched(MessageUpdated::class);
});

it('broadcasts message updated event when reacting', function () {
    Event::fake();

    actingAs($this->agent)
        ->postJson("/chat/messages/{$this->message->id}/reaction", ['emoji' => '🎉']);

    Event::assertDispatched(MessageUpdated::class, function ($event) {
        return $event->message->id === $this->message->id;
    });
});
