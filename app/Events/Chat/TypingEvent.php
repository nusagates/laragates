<?php

namespace App\Events\Chat;

use App\Models\ChatSession;
use App\Models\Agent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TypingEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public ChatSession $session,
        public Agent $agent,
        public bool $isTyping
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-session.' . $this->session->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'agent_id'  => $this->agent->id,
            'name'      => $this->agent->name,
            'is_typing' => $this->isTyping,
        ];
    }
}
