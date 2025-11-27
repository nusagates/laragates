<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ChatMessageController extends Controller
{
    /** ===============================
     *  GET messages of chat session
     *  ===============================*/
    public function index(ChatSession $session)
    {
        return ChatMessage::where('chat_session_id', $session->id)
            ->orderBy('id', 'asc')
            ->get();
    }

    /** ===============================
     *  SEND MESSAGE (TEXT + MEDIA)
     *  ===============================*/
    public function store(Request $request, ChatSession $session)
    {
        if (!$this->checkApproval()) {
            return response()->json(['error' => 'Your account is pending approval'], 403);
        }

        $request->validate([
            'message' => 'nullable|string',
            'media'   => 'nullable|file|max:5120', // 5MB
        ]);

        $text = $request->input('message');
        $mediaUrl = null;
        $mediaType = null;
        $bubbleType = 'text';

        // Upload media
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $name = Str::random(32) . '.' . $file->getClientOriginalExtension();

            $file->storeAs('public/chat-media', $name);
            $mediaUrl = asset('storage/chat-media/' . $name);
            $mediaType = $file->getMimeType();
            $bubbleType = 'media';

            if (!$text) {
                if (Str::contains($mediaType, 'image')) $text = '[photo]';
                elseif (Str::contains($mediaType, 'pdf')) $text = '[pdf]';
                elseif (Str::contains($mediaType, 'video')) $text = '[video]';
                elseif (Str::contains($mediaType, 'audio')) $text = '[audio]';
                else $text = '[file]';
            }
        }

        if (!$text) {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty'], 422);
        }

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => auth()->id(),
            'is_outgoing'     => true,
            'is_internal'     => false,
            'is_bot'          => false,
            'message'         => $text,
            'media_url'       => $mediaUrl,
            'media_type'      => $mediaType,
            'type'            => $bubbleType,
            'status'          => 'sent',
        ]);

        $session->touch(); // update "updated_at" so sidebar sorting updated

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'data'    => $msg
        ]);
    }

    /** ===============================
     *  RETRY
     *  ===============================*/
    public function retry(ChatMessage $message)
    {
        if ($message->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'Message is not failed'], 400);
        }

        $message->update(['status' => 'sent']);
        return response()->json(['success' => true, 'message' => 'Message retried']);
    }

    /** ===============================
     *  MARK READ
     *  ===============================*/
    public function markRead(ChatMessage $message)
    {
        $message->update(['status' => 'read']);
        return response()->json(['success' => true]);
    }

    private function checkApproval(): bool
    {
        return !empty(Auth::user()->approved_at);
    }
}
