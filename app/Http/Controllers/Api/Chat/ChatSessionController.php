<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatSessionController extends Controller
{
    /** SIDEBAR: GET /chat/sessions */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ChatSession::with(['customer', 'lastMessage', 'ticket']);

        // Filter: Only show sessions assigned to this agent (for role=agent)
        if ($user->role === 'agent') {
            $query->where('assigned_to', $user->id);
        }

        // Admin/Superadmin/Supervisor can see all sessions
        // No filter needed for them

        $sessions = $query
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($s) {
                return [
                    'session_id' => $s->id,
                    'customer_name' => $s->customer->name ?? $s->customer->phone,
                    'phone' => $s->customer->phone ?? null,
                    'last_message' => $s->lastMessage->message ?? '',
                    'time' => optional($s->lastMessage?->created_at)->format('H:i'),
                    'unread_count' => 0,
                    'has_ticket' => (bool) $s->ticket,
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
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:30',
            'message' => 'required|string',
            'create_ticket' => 'boolean',
        ]);

        $agent = Auth::user();

        $phone = $this->normalizePhone($data['phone']);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $data['name'] ?: $phone]
        );

        // ==== Gunakan Auto Routing ====
        $assigned = $this->assignAgent() ?? $agent->id;

        $session = ChatSession::create([
            'customer_id' => $customer->id,
            'status' => 'open',
            'assigned_to' => $assigned,
        ]);

        $message = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'agent',
            'user_id' => $agent->id,
            'message' => $data['message'],
            'type' => 'text',
            'status' => 'sent',
            'is_outgoing' => true,
        ]);

        $ticket = null;
        if ($data['create_ticket']) {
            $ticket = $this->createTicketFromSession($session, $message, $agent->id);
        }

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'ticket_id' => $ticket?->id,
        ]);
    }

    /** CONVERT: POST /chat/sessions/{session}/convert-ticket */
    public function convertToTicket(ChatSession $session)
    {
        if ($session->ticket) {
            return response()->json([
                'success' => true,
                'ticket_id' => $session->ticket->id,
            ]);
        }

        $agent = Auth::user();
        $last = $session->lastMessage;
        $cust = $session->customer;

        $ticket = Ticket::create([
            'chat_session_id' => $session->id,
            'customer_name' => $cust->name ?? $cust->phone,
            'customer_phone' => $cust->phone,
            'subject' => $last?->message ?? "WhatsApp Chat #{$session->id}",
            'status' => 'pending',
            'priority' => 'medium',
            'channel' => 'whatsapp',
            'assigned_to' => $agent->id,
            'last_message_at' => now(),
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $agent->id,
            'sender_type' => 'agent',
            'message' => $ticket->subject,
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'ticket_id' => $ticket->id,
        ]);
    }

    private function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        if (str_starts_with($phone, '+')) {
            return $phone;
        }
        if (str_starts_with($phone, '0')) {
            return '+62'.substr($phone, 1);
        }
        if (str_starts_with($phone, '62')) {
            return '+'.$phone;
        }

        return '+'.$phone;
    }

    private function createTicketFromSession(ChatSession $session, ChatMessage $message, $agentId)
    {
        $cust = $session->customer;

        $ticket = Ticket::create([
            'chat_session_id' => $session->id,
            'customer_name' => $cust->name ?? $cust->phone,
            'customer_phone' => $cust->phone,
            'subject' => $message->message,
            'status' => 'pending',
            'priority' => 'medium',
            'channel' => 'whatsapp',
            'assigned_to' => $agentId,
            'last_message_at' => now(),
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_id' => $agentId,
            'sender_type' => 'agent',
            'message' => $message->message,
            'sent_at' => now(),
        ]);

        return $ticket;
    }

    /**
     * Show single session detail
     */
    public function show(Request $request, ChatSession $session)
    {
        $user = $request->user();

        // Authorization: Agent can only see their assigned sessions
        if ($user->role === 'agent' && $session->assigned_to !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized. This session is not assigned to you.',
            ], 403);
        }

        return response()->json([
            'session' => $session->load(['customer', 'lastMessage', 'ticket', 'agent']),
        ]);
    }

    /**
     * Pin session
     */
    public function pin(Request $request, ChatSession $session)
    {
        $user = $request->user();

        // Authorization: Agent can only pin their assigned sessions
        if ($user->role === 'agent' && $session->assigned_to !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized. This session is not assigned to you.',
            ], 403);
        }

        $session->update(['pinned' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Session pinned',
        ]);
    }

    /**
     * Unpin session
     */
    public function unpin(Request $request, ChatSession $session)
    {
        $user = $request->user();

        // Authorization: Agent can only unpin their assigned sessions
        if ($user->role === 'agent' && $session->assigned_to !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized. This session is not assigned to you.',
            ], 403);
        }

        $session->update(['pinned' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Session unpinned',
        ]);
    }

    /**
     * Mark session as read
     */
    public function markRead(Request $request, ChatSession $session)
    {
        $user = $request->user();

        // Authorization: Agent can only mark read their assigned sessions
        if ($user->role === 'agent' && $session->assigned_to !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized. This session is not assigned to you.',
            ], 403);
        }

        // Mark all unread messages in this session as read
        $session->messages()
            ->where('sender', 'customer')
            ->where('delivery_status', '!=', 'read')
            ->update(['delivery_status' => 'read']);

        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
        ]);
    }

    /**
     * Close session
     */
    public function close(Request $request, ChatSession $session)
    {
        $user = $request->user();

        // Authorization: Agent can only close their assigned sessions
        if ($user->role === 'agent' && $session->assigned_to !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized. This session is not assigned to you.',
            ], 403);
        }

        // Prevent closing already closed sessions
        if ($session->status === 'closed') {
            return response()->json([
                'error' => 'Session is already closed.',
            ], 400);
        }

        // Update session status to closed
        $session->update([
            'status' => 'closed',
            'is_handover' => false,
            'closed_at' => now(),
        ]);

        // Optionally create a system message indicating session closure
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'system',
            'message' => 'Chat session closed by '.$user->name,
            'type' => 'text',
            'is_outgoing' => false,
            'status' => 'sent',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session closed successfully',
        ]);
    }

    /**
     * Get available agents for reassignment
     */
    public function getAvailableAgents(Request $request)
    {
        // Get all active agents (online and available)
        $agents = User::where('role', 'agent')
            ->where('is_online', true)
            ->select('id', 'name', 'email')
            ->withCount(['sessions' => function ($query) {
                $query->whereIn('status', ['open', 'pending']);
            }])
            ->orderBy('sessions_count', 'asc')
            ->get();

        return response()->json([
            'agents' => $agents,
        ]);
    }

    /**
     * Reassign session to another agent
     */
    public function reassign(Request $request, ChatSession $session)
    {
        $user = $request->user();

        // Authorization: Agent can only reassign their assigned sessions, Admin/Supervisor can reassign any
        if ($user->role === 'agent' && $session->assigned_to !== $user->id) {
            return response()->json([
                'error' => 'Unauthorized. This session is not assigned to you.',
            ], 403);
        }

        // Validate request
        $data = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $newAgent = User::findOrFail($data['agent_id']);

        // Verify new agent has 'agent' role
        if ($newAgent->role !== 'agent') {
            return response()->json([
                'error' => 'Selected user is not an agent.',
            ], 400);
        }

        // Prevent reassigning to same agent
        if ($session->assigned_to === $newAgent->id) {
            return response()->json([
                'error' => 'Session is already assigned to this agent.',
            ], 400);
        }

        $oldAgent = $session->agent;

        // Update session assignment
        $session->update([
            'assigned_to' => $newAgent->id,
            'status' => 'open',
        ]);

        // Create system message
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'system',
            'message' => sprintf(
                'Chat reassigned from %s to %s by %s',
                $oldAgent->name ?? 'Unassigned',
                $newAgent->name,
                $user->name
            ),
            'type' => 'text',
            'is_outgoing' => false,
            'status' => 'sent',
        ]);

        // Dispatch event for real-time notification
        event(new \App\Events\SessionAssignedEvent($session->fresh()));

        return response()->json([
            'success' => true,
            'message' => 'Session reassigned successfully',
            'assigned_to' => $newAgent->only(['id', 'name', 'email']),
        ]);
    }
}
