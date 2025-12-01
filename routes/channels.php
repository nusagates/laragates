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
