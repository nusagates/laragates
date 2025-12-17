<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Services\MessageDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Sidebar list chats
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
     * Get chat detail
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
     * SEND MESSAGE (AGENT → CUSTOMER)
     */
    public function send(Request $request, ChatSession $session)
    {
        $request->validate([
            'message' => 'nullable|string',
            'media'   => 'nullable|file|max:10240',
        ]);

        $agent = Auth::user();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$session->assigned_to) {
            $session->update(['assigned_to' => $agent->id]);
        }

        // ============================
        // HANDLE MEDIA
        // ============================
        $mediaUrl = null;
        $mediaType = null;
        $isMedia = false;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('chat_media', 'public');

            $mediaUrl  = asset('storage/' . $path);
            $mediaType = $file->getMimeType();
            $isMedia   = true;
        }

        // ============================
        // SAVE MESSAGE (QUEUED)
        // ============================
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => $agent->id,

            'message'         => $request->message ?? '',
            'media_url'       => $mediaUrl,
            'media_type'      => $mediaType,
            'type'            => $isMedia ? 'media' : 'text',

            'status'          => 'pending',
            'delivery_status' => 'queued',

            'is_outgoing'     => true,
            'is_bot'          => false,
        ]);

        $session->touch();

        // ============================
        // SEND VIA DELIVERY ENGINE
        // ============================
        MessageDeliveryService::send($msg);

        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json(['success' => true, 'data' => $msg]);
    }

    /**
     * OUTBOUND (START NEW CHAT)
     */
    public function outbound(Request $request)
    {
        $data = $request->validate([
            'phone'   => 'required|string|max:30',
            'name'    => 'nullable|string|max:150',
            'message' => 'required|string|max:4000',
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $data['name']]
        );

        $session = ChatSession::create([
            'customer_id' => $customer->id,
            'status'      => 'open',
            'assigned_to' => Auth::id(),
        ]);

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => Auth::id(),
            'message'         => $data['message'],
            'type'            => 'text',

            'status'          => 'pending',
            'delivery_status' => 'queued',

            'is_outgoing'     => true,
            'is_bot'          => false,
        ]);

        $session->touch();

        MessageDeliveryService::send($msg);

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
        ]);
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
     * Normalize phone
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
