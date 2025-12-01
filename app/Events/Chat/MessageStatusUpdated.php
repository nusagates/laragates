<?php

namespace App\Events\Chat;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageStatusUpdated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public ChatMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-session.' . $this->message->chat_session_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'status'     => $this->message->status,
        ];
    }
}
