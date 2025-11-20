<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\WabaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * List chat (sidebar kiri)
     */
    public function index()
    {
        $user = Auth::user();

        // pakai relasi lastMessage biar nggak load semua messages
        $query = ChatSession::with(['customer', 'lastMessage'])
            ->orderByDesc('updated_at');

        if ($user && $user->role === 'agent') {
            $query->where('agent_id', $user->id);
        }

        $sessions = $query->limit(50)->get();

        $data = $sessions->map(function (ChatSession $session) {
            $last = $session->lastMessage;

            return [
                'session_id'    => $session->id,
                'customer_name' => $session->customer->name ?? $session->customer->phone,
                'last_message'  => $last?->message ? mb_strimwidth($last->message, 0, 35, '...') : '',
                'time'          => $last?->created_at?->format('H:i'),
                'unread_count'  => 0, // fitur selanjutnya
                'status'        => $session->status,
            ];
        });

        return response()->json($data);
    }

    /**
     * Detail chat + isi pesan (panel kanan)
     */
    public function show(ChatSession $session)
    {
        $user = Auth::user();

        if ($user && $user->role === 'agent' && $session->agent_id !== $user->id) {
            abort(403);
        }

        $session->load(['customer', 'agent', 'messages' => function ($q) {
            $q->orderBy('created_at', 'asc');
        }]);

        $messages = $session->messages->map(function ($m) use ($user) {
            return [
                'id'     => $m->id,
                'sender' => $m->sender, // customer / agent
                'type'   => $m->type,
                'text'   => $m->message,
                'time'   => $m->created_at->format('H:i'),
                'is_me'  => $user
                    ? $m->sender === 'agent' && $m->user_id === $user->id
                    : false,
            ];
        });

        return response()->json([
            'session_id' => $session->id,
            'status'     => $session->status,
            'customer'   => [
                'id'    => $session->customer->id,
                'name'  => $session->customer->name ?? $session->customer->phone,
                'phone' => $session->customer->phone,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Kirim pesan agent -> customer
     */
    public function send(Request $request, ChatSession $session, WabaApiService $waba)
    {
        $user = Auth::user();

        if ($user && $user->role === 'agent' && $session->agent_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        // Auto assign kalau session belum punya agent
        if (!$session->agent_id && $user) {
            $session->update([
                'agent_id'    => $user->id,
                'assigned_at' => now(),
            ]);
        }

        // Kirim WA (MOCK kalau env belum diset, sudah di-handle di WabaApiService)
        $wa   = $waba->sendText($session->customer->phone, $data['message']);
        $waId = $wa['messages'][0]['id'] ?? null;

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => $user?->id,
            'message'         => $data['message'],
            'type'            => 'text',
            'wa_message_id'   => $waId,
        ]);

        $session->touch();

        return response()->json([
            'id'     => $msg->id,
            'sender' => 'agent',
            'is_me'  => true,
            'text'   => $msg->message,
            'time'   => $msg->created_at->format('H:i'),
        ]);
    }

    /**
     * Assign chat ke agent
     */
    public function assign(Request $request, ChatSession $session)
    {
        $data = $request->validate([
            'agent_id' => ['required', 'exists:users,id'],
        ]);

        $session->update([
            'agent_id'    => $data['agent_id'],
            'assigned_at' => now(),
            'status'      => 'open',
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Close chat session
     */
    public function close(ChatSession $session)
    {
        $session->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
