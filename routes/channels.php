<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

Broadcast::channel('agent.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat-session.{sessionId}', function ($user, $sessionId) {
    // Allow authenticated users to listen to chat sessions
    // You can add additional authorization logic here if needed
    return $user !== null;
});
