<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatMessageController extends Controller
{
    /**
     * GET message list of chat session
     */
    public function index(ChatSession $session)
    {
        return ChatMessage::where('chat_session_id', $session->id)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * STORE: Send Message (Text + Media)
     */
    public function store(Request $request, ChatSession $session)
    {
        // Validate minimal rule
        $request->validate([
            'message'   => 'nullable|string',
            'media'     => 'nullable|file|max:5120', // 5MB
        ]);

        $text       = $request->input('message');
        $mediaUrl   = null;
        $mediaType  = null;
        $bubbleType = 'text';

        /** ===============================
         *  UPLOAD MEDIA (if exists)
         *  ===============================*/
        if ($request->hasFile('media')) {
            $file = $request->file('media');

            // Generate filename
            $name = Str::random(32) . '.' . $file->getClientOriginalExtension();

            // Save to storage/app/public/chat-media
            $path = $file->storeAs('public/chat-media', $name);

            // Convert to full URL
            $mediaUrl = asset('storage/chat-media/' . $name);

            // Set MIME & message type
            $mediaType = $file->getMimeType();
            $bubbleType = 'media';

            // If media uploaded but no text, auto-fill text bubble
            if (!$text) {
                if (Str::contains($mediaType, 'image')) {
                    $text = '[photo]';
                } elseif (Str::contains($mediaType, 'pdf')) {
                    $text = '[pdf]';
                } elseif (Str::contains($mediaType, 'video')) {
                    $text = '[video]';
                } elseif (Str::contains($mediaType, 'audio')) {
                    $text = '[audio]';
                } else {
                    $text = '[file]';
                }
            }
        }

        /** ===============================
         *  Prevent message NULL
         *  ===============================*/
        if (!$text) {
            return response()->json([
                'success' => false,
                'message' => 'Message cannot be empty if no media uploaded',
            ], 422);
        }

        /** ===============================
         *  Save to Database
         *  ===============================*/
        $msg = ChatMessage::create([
            'chat_session_id'   => $session->id,
            'sender'            => 'agent',
            'user_id'           => auth()->id(),
            'is_outgoing'       => true,
            'is_internal'       => false,
            'is_bot'            => false,
            'message'           => $text,
            'media_url'         => $mediaUrl,
            'media_type'        => $mediaType,
            'type'              => $bubbleType,
            'status'            => 'sent',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent',
            'data' => $msg
        ]);
    }


    /**
     * RETRY Failed outgoing message (manual)
     */
    public function retry(ChatMessage $message)
    {
        if ($message->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'Message is not failed'], 400);
        }

        // Example retry logic (future I/O WhatsApp API)
        $message->update(['status' => 'sent']);

        return response()->json(['success' => true, 'message' => 'Message retried']);
    }


    /**
     * Mark message as read (manual)
     */
    public function markRead(ChatMessage $message)
    {
        $message->update(['status' => 'read']);
        return response()->json(['success' => true]);
    }
}
