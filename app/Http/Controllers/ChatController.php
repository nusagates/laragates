<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\WabaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = ChatSession::with(['customer', 'lastMessage'])
            ->orderByDesc('updated_at');

        if ($user && $user->role === 'agent') {
            $query->where('agent_id', $user->id);
        }

        $sessions = $query->limit(50)->get();

        return $sessions->map(function ($session) {
            $last = $session->lastMessage;

            return [
                'session_id'    => $session->id,
                'customer_name' => $session->customer->name ?? $session->customer->phone,
                'last_message'  => $last?->message ? mb_strimwidth($last->message, 0, 35, '...') : '',
                'time'          => $last?->created_at?->format('H:i'),
                'unread_count'  => 0,
                'status'        => $session->status,
            ];
        });
    }

    public function show(ChatSession $session)
    {
        $session->load([
            'customer',
            'agent',
            'messages' => fn($q) => $q->orderBy('created_at', 'asc')
        ]);

        return [
            'session_id' => $session->id,
            'status'     => $session->status,
            'customer'   => [
                'id'    => $session->customer->id,
                'name'  => $session->customer->name ?? $session->customer->phone,
                'phone' => $session->customer->phone,
            ],
            'messages' => $session->messages->map(fn($m) => [
                'id'     => $m->id,
                'sender' => $m->sender,
                'type'   => $m->type,
                'text'   => $m->message,
                'time'   => $m->created_at->format('H:i'),
            ]),
        ];
    }

    public function send(Request $request, ChatSession $session, WabaApiService $waba)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => Auth::id(),
            'message'         => $data['message'],
            'type'            => 'text'
        ]);

        $session->touch();

        broadcast(new \App\Events\MessageSent($msg))->toOthers();

        return [
            'id'     => $msg->id,
            'sender' => 'agent',
            'is_me'  => true,
            'text'   => $msg->message,
            'time'   => $msg->created_at->format('H:i'),
        ];
    }
}
