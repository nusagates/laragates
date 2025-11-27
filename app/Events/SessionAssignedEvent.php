<?php

namespace App\Events;

use App\Models\ChatSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SessionAssignedEvent implements ShouldBroadcast
{
    use SerializesModels;

    public ChatSession $session;

    public function __construct(ChatSession $session)
    {
        $this->session = $session->load('customer', 'agent');
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('agent.' . $this->session->assigned_to);
    }

    public function broadcastAs(): string
    {
        return 'session.assigned';
    }
}
