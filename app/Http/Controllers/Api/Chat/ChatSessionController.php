<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatSessionController extends Controller
{
    /** SIDEBAR: GET /chat/sessions */
    public function index()
    {
        $sessions = ChatSession::with(['customer', 'lastMessage', 'ticket'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($s) {
                return [
                    'session_id'    => $s->id,
                    'customer_name' => $s->customer->name ?? $s->customer->phone,
                    'phone'         => $s->customer->phone ?? null,
                    'last_message'  => $s->lastMessage->message ?? '',
                    'time'          => optional($s->lastMessage?->created_at)->format('H:i'),
                    'unread_count'  => 0,
                    'has_ticket'    => (bool) $s->ticket,
                ];
            });

        return response()->json($sessions);
    }

    /** ===============================
     *      AUTO ROUTING AGENT
     * ===============================*/
    private function assignAgent()
    {
        $agents = User::availableAgent()->get();

        if ($agents->isEmpty()) {
            return null; // Tidak ada agent online → unassigned
        }

        // Cari agent dengan jumlah chat aktif paling sedikit
        $agent = $agents->sortBy(function ($a) {
            return $a->sessions()->where('status', 'open')->count();
        })->first();

        return $agent->id;
    }

    /** CREATE OUTBOUND: POST /chat/sessions/outbound */
    public function outbound(Request $request)
    {
        $data = $request->validate([
            'name'          => 'nullable|string|max:255',
            'phone'         => 'required|string|max:30',
            'message'       => 'required|string',
            'create_ticket' => 'boolean',
        ]);

        $agent = Auth::user();

        $phone = $this->normalizePhone($data['phone']);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name'  => $data['name'] ?: $phone]
        );

        // ==== Gunakan Auto Routing ====
        $assigned = $this->assignAgent() ?? $agent->id;

        $session = ChatSession::create([
            'customer_id' => $customer->id,
            'status'      => 'open',
            'assigned_to' => $assigned,
        ]);

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => $agent->id,
            'message'         => $data['message'],
            'type'            => 'text',
            'status'          => 'sent',
            'is_outgoing'     => true,
        ]);

        $ticket = null;
        if ($data['create_ticket']) {
            $ticket = $this->createTicketFromSession($session, $message, $agent->id);
        }

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
            'ticket_id'  => $ticket?->id,
        ]);
    }

    /** CONVERT: POST /chat/sessions/{session}/convert-ticket */
    public function convertToTicket(ChatSession $session)
    {
        if ($session->ticket) {
            return response()->json([
                'success'   => true,
                'ticket_id' => $session->ticket->id,
            ]);
        }

        $agent = Auth::user();
        $last  = $session->lastMessage;
        $cust  = $session->customer;

        $ticket = Ticket::create([
            'chat_session_id' => $session->id,
            'customer_name'   => $cust->name ?? $cust->phone,
            'customer_phone'  => $cust->phone,
            'subject'         => $last?->message ?? "WhatsApp Chat #{$session->id}",
            'status'          => 'pending',
            'priority'        => 'medium',
            'channel'         => 'whatsapp',
            'assigned_to'     => $agent->id,
            'last_message_at' => now(),
        ]);

        TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $agent->id,
            'sender_type' => 'agent',
            'message'     => $ticket->subject,
            'sent_at'     => now(),
        ]);

        return response()->json([
            'success'   => true,
            'ticket_id' => $ticket->id,
        ]);
    }

    private function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        if (str_starts_with($phone, '+')) return $phone;
        if (str_starts_with($phone, '0')) return '+62' . substr($phone, 1);
        if (str_starts_with($phone, '62')) return '+' . $phone;
        return '+' . $phone;
    }

    private function createTicketFromSession(ChatSession $session, ChatMessage $message, $agentId)
    {
        $cust = $session->customer;

        $ticket = Ticket::create([
            'chat_session_id' => $session->id,
            'customer_name'   => $cust->name ?? $cust->phone,
            'customer_phone'  => $cust->phone,
            'subject'         => $message->message,
            'status'          => 'pending',
            'priority'        => 'medium',
            'channel'         => 'whatsapp',
            'assigned_to'     => $agentId,
            'last_message_at' => now(),
        ]);

        TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $agentId,
            'sender_type' => 'agent',
            'message'     => $message->message,
            'sent_at'     => now(),
        ]);

        return $ticket;
    }
}
