<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TicketController extends Controller
{
    /**
     * Halaman utama ticket
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('q');

        $query = Ticket::with('agent')
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'ongoing', 'closed'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $tickets = $query->get()->map(function ($t) {
            return [
                'id'            => $t->id,
                'customer_name' => $t->customer_name,
                'subject'       => $t->subject,
                'status'        => $t->status,
                'priority'      => $t->priority,
                'channel'       => $t->channel,
                'assigned_to'   => $t->assigned_to,
                'assigned_name' => $t->agent?->name,
                'last_message_at' => optional($t->last_message_at)->format('H:i'),
            ];
        });

        $counts = [
            'all'     => Ticket::count(),
            'pending' => Ticket::where('status', 'pending')->count(),
            'ongoing' => Ticket::where('status', 'ongoing')->count(),
            'closed'  => Ticket::where('status', 'closed')->count(),
        ];

        // List agents untuk assign dropdown
        $agents = User::whereIn('role', ['agent', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'counts'  => $counts,
            'filters' => [
                'status' => $status,
                'q'      => $search,
            ],
            'agents'  => $agents,
        ]);
    }

    /**
     * Detail Ticket + Messages (AJAX)
     */
    public function show(Ticket $ticket)
    {
        $ticket->load(['agent', 'messages.sender']);

        return [
            'ticket' => [
                'id'            => $ticket->id,
                'customer_name' => $ticket->customer_name,
                'customer_phone'=> $ticket->customer_phone,
                'subject'       => $ticket->subject,
                'status'        => $ticket->status,
                'priority'      => $ticket->priority,
                'channel'       => $ticket->channel,
                'assigned_to'   => $ticket->assigned_to,
                'assigned_name' => $ticket->agent?->name,
                'created_at'    => $ticket->created_at->format('d M Y H:i'),
            ],
            'messages' => $ticket->messages->map(function ($m) {
                return [
                    'id'          => $m->id,
                    'sender_type' => $m->sender_type,
                    'sender_name' => $m->sender?->name ?? 'Customer',
                    'is_me'       => $m->sender_id && $m->sender_id === Auth::id(),
                    'message'     => $m->message,
                    'time'        => $m->created_at->timezone('Asia/Jakarta')->format('H:i'),
                ];
            }),
        ];
    }

    /**
     * Kirim balasan oleh Agent
     */
    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        $msg = TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'agent',
            'sender_id'   => $user->id,
            'message'     => $request->message,
        ]);

        // Update ticket state
        if ($ticket->status === 'pending') {
            $ticket->status = 'ongoing';
        }
        if (!$ticket->assigned_to) {
            $ticket->assigned_to = $user->id;
        }
        $ticket->last_message_at = now();
        $ticket->save();

        return [
            'id'          => $msg->id,
            'sender_type' => 'agent',
            'sender_name' => $user->name,
            'is_me'       => true,
            'message'     => $msg->message,
            'time'        => $msg->created_at->format('H:i'),
        ];
    }

    /**
     * Update Status Ticket
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,ongoing,closed',
        ]);

        $ticket->status = $data['status'];
        $ticket->save();

        return back()->with('success', 'Ticket status updated.');
    }

    /**
     * Assign Ticket to Agent
     */
    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->assigned_to = $data['assigned_to'];
        $ticket->save();

        return back()->with('success', 'Ticket assigned.');
    }
}
