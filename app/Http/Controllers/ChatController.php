<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\WabaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Sidebar list chats (/api/chats)
     */
    public function index()
    {
        $query = ChatSession::with(['customer', 'lastMessage'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return $query->map(function ($session) {
            $last = $session->lastMessage;

            return [
                'session_id'    => $session->id,
                'customer_name' => $session->customer->name ?? $session->customer->phone,
                'last_message'  => $last?->message 
                    ? mb_strimwidth($last->message, 0, 35, '...')
                    : '',
                'time'          => $last?->created_at?->format('H:i'),
                'unread_count'  => 0,
                'status'        => $session->status,
            ];
        });
    }

    /**
     * Detail messages of one chat (/api/chats/{session})
     */
    public function show(ChatSession $session)
    {
        $session->load([
            'customer',
            'agent',
            'messages' => fn ($q) => $q->orderBy('created_at', 'asc'),
        ]);

        return [
            'session_id' => $session->id,
            'status'     => $session->status,
            'customer'   => [
                'id'    => $session->customer->id,
                'name'  => $session->customer->name ?? $session->customer->phone,
                'phone' => $session->customer->phone,
            ],
            'messages' => $session->messages->map(fn ($m) => [
                'id'     => $m->id,
                'sender' => $m->sender,
                'type'   => $m->type,
                'text'   => $m->message,
                'time'   => $m->created_at->format('H:i'),
                'is_me'  => $m->sender === 'agent' && $m->user_id === Auth::id(),
            ]),
        ];
    }

    /**
     * Send message inside chat (/api/chats/{session}/send)
     */
    public function send(Request $request, ChatSession $session)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $agent = Auth::user();
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized user'
            ], 401);
        }

        // auto assign if empty
        if (!$session->assigned_to) {
            $session->update(['assigned_to' => $agent->id]);
        }

        // create message
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => $agent->id,
            'message'         => $request->message,
            'type'            => 'text',
            'status'          => 'sent',
            'is_outgoing'     => true,
            'is_internal'     => false,
            'is_bot'          => false,
        ]);

        // touch session activity
        $session->touch();

        // broadcast (ignore error if pusher off)
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'data'    => $msg
        ], 200);
    }

    /**
     * NEW: Outbound chat (agent sends first) (/api/chats/outbound)
     */
    public function outbound(Request $request, WabaApiService $waba)
    {
        $data = $request->validate([
            'phone'         => ['required', 'string', 'max:30'],
            'name'          => ['nullable', 'string', 'max:150'],
            'message'       => ['required', 'string', 'max:4000'],
            'create_ticket' => ['sometimes', 'boolean'],
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $data['name']]
        );

        // always create new chat session
        $session = ChatSession::create([
            'customer_id' => $customer->id,
            'status'      => 'open',
        ]);

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => Auth::id(),
            'message'         => $data['message'],
            'type'            => 'text',
        ]);

        $session->touch();

        try {
            $waba->sendText($customer->phone, $data['message']);
        } catch (\Throwable $e) {}

        $ticketId = null;
        if ($request->boolean('create_ticket')) {
            $subject = mb_strimwidth($data['message'], 0, 80, '...');

            $ticket = Ticket::create([
                'customer_name'   => $customer->name ?? $customer->phone,
                'customer_phone'  => $customer->phone,
                'subject'         => $subject,
                'status'          => 'pending',
                'priority'        => 'medium',
                'channel'         => 'whatsapp',
                'assigned_to'     => Auth::id(),
                'last_message_at' => now(),
            ]);

            TicketMessage::create([
                'ticket_id'   => $ticket->id,
                'sender_type' => 'agent',
                'sender_id'   => Auth::id(),
                'message'     => $data['message'],
            ]);

            $ticketId = $ticket->id;
        }

        try {
            broadcast(new \App\Events\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
            'ticket_id'  => $ticketId,
        ]);
    }

    /**
     * Assign chat
     */
    public function assign(Request $request, ChatSession $session)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $session->assigned_to = $data['assigned_to'];
        $session->save();

        return response()->json(['success' => true]);
    }

    /**
     * Close chat
     */
    public function close(ChatSession $session)
    {
        $session->status = 'closed';
        $session->save();

        return response()->json(['success' => true]);
    }

    /**
     * Normalize phone to +62xxxx format
     */
    protected function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', $phone ?? '');

        if (!$clean) return '';

        if (str_starts_with($clean, '+')) return $clean;
        if (str_starts_with($clean, '0')) return '+62' . substr($clean, 1);
        if (str_starts_with($clean, '62')) return '+' . $clean;

        return '+' . $clean;
    }
}
