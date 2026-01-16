<?php

namespace App\Events\Chat;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public $sessionId;

    public function __construct(ChatMessage $message)
    {
        $this->sessionId = $message->chat_session_id;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat-session.'.$this->sessionId);
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'session_id' => $this->message->chat_session_id,
                'sender' => $this->message->sender,
                'message' => $this->message->message,
                'media_url' => $this->message->media_url,
                'media_type' => $this->message->media_type,
                'delivery_status' => $this->message->delivery_status,
                'created_at' => $this->message->created_at->toISOString(),
                'reactions' => $this->message->reactions ?? [],
            ],
        ];
    }
}
