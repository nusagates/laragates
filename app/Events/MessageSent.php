<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payload;
    public $sessionId;

    public function __construct(ChatMessage $message)
    {
        $this->sessionId = $message->chat_session_id;

        $this->payload = [
            'id'    => $message->id,
            'sender'=> $message->sender,
            'text'  => $message->message,
            'time'  => $message->created_at->format('H:i'),
        ];
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->sessionId);
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}
