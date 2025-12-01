<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Events\Chat\TypingEvent;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypingController extends Controller
{
    public function typing(Request $request, ChatSession $session)
    {
        $data = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        broadcast(new TypingEvent(
            session: $session,
            agent: Auth::user(),
            isTyping: $data['is_typing']
        ))->toOthers();

        return response()->noContent();
    }
}
