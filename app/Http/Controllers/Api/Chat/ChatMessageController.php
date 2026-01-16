<?php

namespace App\Http\Controllers\Api\Chat;

use App\Events\Chat\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\MessageDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatMessageController extends Controller
{
    /**
     * GET messages of chat session
     */
    public function index(ChatSession $session)
    {
        return ChatMessage::where('chat_session_id', $session->id)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * SEND MESSAGE (TEXT + MEDIA)
     */
    public function store(Request $request, ChatSession $session)
    {
        if (! $this->checkApproval()) {
            return response()->json(['error' => 'Your account is pending approval'], 403);
        }

        $request->validate([
            'message' => 'nullable|string',
            'media' => 'nullable|file|max:5120',
        ]);

        $text = $request->input('message');
        $mediaUrl = null;
        $mediaType = null;
        $bubbleType = 'text';

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $name = Str::random(32).'.'.$file->getClientOriginalExtension();

            $file->storeAs('public/chat-media', $name);
            $mediaUrl = asset('storage/chat-media/'.$name);
            $mediaType = $file->getMimeType();
            $bubbleType = 'media';

            if (! $text) {
                $text = '[media]';
            }
        }

        if (! $text) {
            return response()->json(['success' => false, 'message' => 'Message cannot be empty'], 422);
        }

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'agent',
            'user_id' => Auth::id(),

            'message' => $text,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'type' => $bubbleType,

            'status' => 'pending',
            'delivery_status' => 'queued',

            'is_outgoing' => true,
            'is_internal' => false,
            'is_bot' => false,
        ]);

        $session->touch();

        MessageDeliveryService::send($msg);

        return response()->json([
            'success' => true,
            'message' => 'Message queued',
            'data' => $msg,
        ]);
    }

    private function checkApproval(): bool
    {
        return ! empty(Auth::user()->approved_at);
    }

    /**
     * Add a reaction to a message
     */
    public function addReaction(Request $request, ChatMessage $message): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $reactions = $message->reactions ?? [];
        $emoji = $request->emoji;

        // Initialize emoji count if doesn't exist
        if (! isset($reactions[$emoji])) {
            $reactions[$emoji] = 0;
        }

        // Increment reaction count
        $reactions[$emoji]++;

        $message->update(['reactions' => $reactions]);

        // Broadcast the update
        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json([
            'status' => 'ok',
            'message' => $message->fresh(),
        ]);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(ChatMessage $message): \Illuminate\Http\JsonResponse
    {
        $message->update([
            'delivery_status' => 'read',
        ]);

        // Broadcast the update
        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json([
            'status' => 'ok',
            'message' => $message->fresh(),
        ]);
    }
}
