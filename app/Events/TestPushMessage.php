<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestPushMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public string $timestamp;

    public function __construct(string $message)
    {
        $this->message = $message;
        $this->timestamp = now()->format('Y-m-d H:i:s');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('test-push-message'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TestMessageReceived';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'timestamp' => $this->timestamp,
        ];
    }
}
