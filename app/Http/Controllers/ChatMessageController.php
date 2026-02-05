<?php

namespace App\Http\Controllers\Api\Chat;

use App\Events\Chat\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatMessageController extends Controller
{
    public function store(Request $request, ChatSession $session)
    {
        $request->validate([
            'message' => 'nullable|string',
            'media' => 'nullable|file|max:10240', // 10 MB
        ]);

        DB::transaction(function () use ($request, $session) {

            $mediaPath = null;
            $mediaType = null;
            $mediaMime = null;
            $mediaSize = null;

            if ($request->hasFile('media')) {
                $file = $request->file('media');

                $mediaPath = $file->store('chat-media', 'public');
                $mediaMime = $file->getMimeType();
                $mediaSize = $file->getSize();

                $mediaType = match (true) {
                    str_starts_with($mediaMime, 'image/') => 'image',
                    str_starts_with($mediaMime, 'video/') => 'video',
                    default => 'file',
                };
            }

            $message = ChatMessage::create([
                'chat_session_id' => $session->id,
                'user_id' => Auth::id(),
                'is_outgoing' => true,
                'message' => $request->message,
                'media_path' => $mediaPath,
                'media_type' => $mediaType,
                'media_mime' => $mediaMime,
                'media_size' => $mediaSize,
            ]);

            SystemLog::create([
                'event' => 'chat_message_sent',
                'entity_type' => 'chat_message',
                'entity_id' => $message->id,
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role,
                'ip_address' => $request->ip(),
                'meta' => json_encode([
                    'has_media' => (bool) $mediaPath,
                    'media_type' => $mediaType,
                ]),
            ]);
        });

        return response()->json(['status' => 'ok']);
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
