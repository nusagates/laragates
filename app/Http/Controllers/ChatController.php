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
     * List chat sessions untuk sidebar Chat.
     * Dipakai oleh /api/chats
     */
    public function index()
    {
        $user = Auth::user();

        $query = ChatSession::with(['customer', 'lastMessage'])
            ->orderByDesc('updated_at');

        // Kalau nanti mau filter per agent:
        // if ($user && $user->role === 'agent') {
        //     $query->where('assigned_to', $user->id);
        // }

        $sessions = $query->limit(50)->get();

        return $sessions->map(function ($session) {
            $last = $session->lastMessage;

            return [
                'session_id'    => $session->id,
                'customer_name' => $session->customer->name ?? $session->customer->phone,
                'last_message'  => $last?->message
                    ? mb_strimwidth($last->message, 0, 35, '...')
                    : '',
                'time'          => $last?->created_at?->format('H:i'),
                'unread_count'  => 0, // kalau mau ada fitur unread nanti
                'status'        => $session->status,
            ];
        });
    }

    /**
     * Detail satu chat session: customer + semua message.
     * Dipakai oleh /api/chats/{session}
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
                'sender' => $m->sender, // 'customer' / 'agent'
                'type'   => $m->type,
                'text'   => $m->message,
                'time'   => $m->created_at->format('H:i'),
                'is_me'  => $m->sender === 'agent' && $m->user_id === Auth::id(),
            ]),
        ];
    }

    /**
     * Kirim pesan balasan dari agent di dalam satu session yang sudah ada.
     * Dipakai oleh /api/chats/{session}/send
     */
    public function send(Request $request, ChatSession $session, WabaApiService $waba)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        // Simpan ke DB
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => Auth::id(),
            'message'         => $data['message'],
            'type'            => 'text',
        ]);

        // Update last activity session
        $session->touch();

        // Kirim ke WhatsApp (wrap dengan try supaya kalau error WA, app tetap jalan)
        try {
            $waba->sendText(
                $session->customer->phone,
                $data['message']
            );
        } catch (\Throwable $e) {
            // Bisa di-log kalau mau
            // logger()->error('Failed send WA', ['error' => $e->getMessage()]);
        }

        // Broadcast realtime ke CS lain
        try {
            broadcast(new \App\Events\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {
            // ignore
        }

        return [
            'id'     => $msg->id,
            'sender' => 'agent',
            'is_me'  => true,
            'text'   => $msg->message,
            'time'   => $msg->created_at->format('H:i'),
        ];
    }

    /**
     * NEW: Outbound chat
     * CS kirim duluan ke nomor (baru / existing).
     * Optional: create ticket sekaligus.
     *
     * Endpoint: POST /api/chats/outbound
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

        // 1. Find or create customer
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $data['name']]
        );

        // 2. Always create NEW chat session (sesuai jawaban kamu: opsi 2)
        $session = ChatSession::create([
            'customer_id' => $customer->id,
            'status'      => 'open',
            // 'assigned_to' => Auth::id(), // kalau mau auto assign ke agent saat ini
        ]);

        // 3. Simpan message sebagai agent
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => Auth::id(),
            'message'         => $data['message'],
            'type'            => 'text',
        ]);

        $session->touch();

        // 4. Kirim ke WhatsApp
        try {
            $waba->sendText($customer->phone, $data['message']);
        } catch (\Throwable $e) {
            // bisa di-log kalau mau
        }

        // 5. Optional: create ticket sekaligus
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

        // 6. Broadcast ke CS lain kalau perlu
        try {
            broadcast(new \App\Events\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {
            // ignore
        }

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
            'ticket_id'  => $ticketId,
        ]);
    }

    /**
     * Assign chat session ke agent tertentu.
     * (kalau nanti mau dipakai)
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
     * Close chat session.
     */
    public function close(ChatSession $session)
    {
        $session->status = 'closed';
        $session->save();

        return response()->json(['success' => true]);
    }

    /**
     * Helper: normalisasi nomor WA ke format +62xxxx
     */
    protected function normalizePhone(string $phone): string
    {
        // buang semua selain digit dan +
        $clean = preg_replace('/[^0-9+]/', '', $phone ?? '');

        if (!$clean) {
            return '';
        }

        // sudah +62xxx
        if (str_starts_with($clean, '+')) {
            return $clean;
        }

        // 0xxxx -> +62xxxx
        if (str_starts_with($clean, '0')) {
            return '+62' . substr($clean, 1);
        }

        // 62xxxx -> +62xxxx
        if (str_starts_with($clean, '62')) {
            return '+' . $clean;
        }

        // default: tambah +
        return '+' . $clean;
    }
}
