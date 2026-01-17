<?php

namespace App\Events\Chat;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {

        return new PrivateChannel('chat-session.'.$this->message->chat_session_id);
    }

    public function broadcastAs()
    {
        return 'message-updated';
    }

    public function broadcastWith(): array
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
                'status' => $this->message->status,
                'created_at' => $this->message->created_at,
                'reactions' => $this->message->reactions,
                'is_outgoing' => $this->message->is_outgoing,
                'is_internal' => $this->message->is_internal,
            ],
        ];
    }
}
