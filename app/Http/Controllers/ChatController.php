<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Services\ContactIntelligenceService;
use App\Services\ContactScoringService;
use App\Services\MessageDeliveryService;
use App\Services\SlaService;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * ===============================
     * SIDEBAR LIST CHATS
     * ===============================
     */
    public function index()
    {
        SystemLogService::record(
            event: 'chat_list_access',
            meta: [
                'path' => request()->path(),
                'method' => request()->method(),
            ]
        );

        $sessions = ChatSession::with(['customer', 'lastMessage'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return $sessions->map(function ($session) {
            $last = $session->lastMessage;

            return [
                'session_id' => $session->id,
                'customer_name' => $session->customer->name ?? $session->customer->phone,
                'last_message' => $last?->message
                    ? mb_strimwidth($last->message, 0, 35, '...')
                    : '',
                'time' => $last?->created_at?->format('H:i'),
                'unread_count' => 0,
                'status' => $session->status,
                'sla' => $this->getSlaBadge($session),
            ];
        });
    }

    /**
     * ===============================
     * SLA BADGE LOGIC
     * ===============================
     */
    protected function getSlaBadge(ChatSession $session): ?string
    {
        if ($session->sla_status === 'breach') {
            return 'breach';
        }
        if ($session->sla_status === 'meet') {
            return 'meet';
        }

        if (
            $session->status === 'open' &&
            $session->first_response_at === null
        ) {
            $limitSeconds = config('sla.first_response_minutes') * 60;
            $elapsed = now()->diffInSeconds($session->created_at);

            if ($elapsed >= ($limitSeconds * 0.8)) {
                return 'warning';
            }
        }

        return null;
    }

    /**
     * ===============================
     * CHAT DETAIL
     * ===============================
     */
    public function show(ChatSession $session)
    {
        SystemLogService::record(
            event: 'chat_open_room',
            entityType: 'chat_session',
            entityId: $session->id,
            meta: [
                'customer_phone' => $session->customer?->phone,
            ]
        );

        $session->load([
            'customer',
            'agent',
            'messages' => fn ($q) => $q->orderBy('created_at', 'asc'),
        ]);

        return [
            'session_id' => $session->id,
            'status' => $session->status,
            'customer' => [
                'id' => $session->customer->id,
                'name' => $session->customer->name ?? $session->customer->phone,
                'phone' => $session->customer->phone,
            ],
            'messages' => $session->messages->map(fn ($m) => [
                'id' => $m->id,
                'sender' => $m->sender,
                'type' => $m->type,
                'text' => $m->message,
                'media' => $m->media_url,
                'time' => $m->created_at->format('H:i'),
                'is_me' => $m->sender === 'agent' && $m->user_id === Auth::id(),
            ]),
        ];
    }

    /**
     * ===============================
     * SEND MESSAGE (AGENT → CUSTOMER)
     * ===============================
     */
    public function send(Request $request, ChatSession $session)
    {
        $request->validate([
            'message' => 'nullable|string',
            'media' => 'nullable|file|max:10240',
        ]);

        $agent = Auth::user();
        if (! $agent) {
            return response()->json(['success' => false], 401);
        }

        /**
         * 🚫 BLACKLIST ENFORCEMENT
         */
        if ($session->customer?->is_blacklisted) {

            SystemLogService::record(
                event: 'chat_blocked_blacklist',
                entityType: 'customer',
                entityId: $session->customer->id,
                meta: [
                    'session_id' => $session->id,
                    'attempt' => 'send_message',
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Customer is blacklisted',
            ], 403);
        }

        if (! $session->assigned_to) {
            $session->update(['assigned_to' => $agent->id]);
        }

        $mediaUrl = null;
        $mediaType = null;
        $isMedia = false;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('chat_media', 'public');

            $mediaUrl = asset('storage/'.$path);
            $mediaType = $file->getMimeType();
            $isMedia = true;
        }

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'agent',
            'user_id' => $agent->id,
            'message' => $request->message ?? '',
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'type' => $isMedia ? 'media' : 'text',
            'status' => 'pending',
            'delivery_status' => 'queued',
            'is_outgoing' => true,
            'is_bot' => false,
        ]);

        /**
         * 📊 CONTACT STATS UPDATE
         */
        if ($session->customer) {
            $session->customer->increment('total_messages');
            $session->customer->update([
                'last_contacted_at' => now(),
            ]);
            ContactIntelligenceService::evaluate($session->customer);
            ContactScoringService::evaluate($session->customer);
        }

        SystemLogService::record(
            event: $isMedia ? 'chat_send_media' : 'chat_send_text',
            entityType: 'chat_session',
            entityId: $session->id,
            meta: [
                'media_type' => $mediaType,
                'filename' => $request->file('media')?->getClientOriginalName(),
            ]
        );

        $session->touch();

        /**
         * 🔔 SLA — FIRST RESPONSE
         */
        SlaService::recordFirstResponse($session);

        MessageDeliveryService::send($msg);

        return response()->json(['success' => true]);
    }

    /**
     * ===============================
     * OUTBOUND (START NEW CHAT)
     * ===============================
     */
    public function outbound(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:30',
            'name' => 'nullable|string|max:150',
            'message' => 'required|string|max:4000',
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $data['name']]
        );

        /**
         * 🚫 BLACKLIST ENFORCEMENT
         */
        if ($customer->is_blacklisted) {

            SystemLogService::record(
                event: 'chat_outbound_blocked_blacklist',
                entityType: 'customer',
                entityId: $customer->id,
                meta: [
                    'phone' => $phone,
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Customer is blacklisted',
            ], 403);
        }

        $session = ChatSession::create([
            'customer_id' => $customer->id,
            'status' => 'open',
            'assigned_to' => Auth::id(),
        ]);

        /**
         * 📊 CONTACT STATS
         */
        $customer->increment('total_chats');
        $customer->update([
            'last_contacted_at' => now(),
        ]);
        ContactIntelligenceService::evaluate($customer);
        ContactScoringService::evaluate($customer);

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'agent',
            'user_id' => Auth::id(),
            'message' => $data['message'],
            'type' => 'text',
            'status' => 'pending',
            'delivery_status' => 'queued',
            'is_outgoing' => true,
            'is_bot' => false,
        ]);

        SystemLogService::record(
            event: 'chat_outbound_start',
            entityType: 'chat_session',
            entityId: $session->id,
            meta: ['phone' => $phone]
        );

        /**
         * 🔔 SLA — OUTBOUND = FIRST RESPONSE
         */
        SlaService::recordFirstResponse($session);

        MessageDeliveryService::send($msg);

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
        ]);
    }

    /**
     * ===============================
     * CLOSE CHAT
     * ===============================
     */
    public function close(ChatSession $session)
    {
        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        SlaService::recordResolution($session);

        SystemLogService::record(
            event: 'chat_closed',
            entityType: 'chat_session',
            entityId: $session->id
        );

        return response()->json(['success' => true]);
    }

    /**
     * ===============================
     * WEBHOOK: UPDATE MESSAGE STATUS
     * ===============================
     * Menerima status update dari Fonnte untuk delivery tracking
     */
    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'device' => 'nullable|string',
            'id' => 'nullable|string',  // Optional: only present in "sent" status
            'stateid' => 'nullable|string',
            'status' => 'nullable|string',  // Optional: only present in "sent" status
            'state' => 'nullable|string',  // Present in all statuses
        ]);

        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON payload',
            ], 400);
        }

        $device = $data['device'] ?? null;
        $id = $data['id'] ?? null;
        $stateid = $data['stateid'] ?? null;
        $status = $data['status'] ?? null;
        $state = $data['state'] ?? null;

        // Log webhook untuk debugging
        SystemLogService::record(
            event: 'webhook_status_update',
            meta: [
                'device' => $device,
                'id' => $id,
                'stateid' => $stateid,
                'status' => $status,
                'state' => $state,
            ]
        );

        // Cari message berdasarkan wa_message_id atau state_id
        // Payload "sent" memiliki "id", sedangkan "delivered"/"read" hanya memiliki "stateid"
        $message = null;

        if ($id) {
            // Handle both formats: "139314661" and "[139314661]"
            $message = ChatMessage::whereIn('wa_message_id', [$id, "[$id]"])->first();
        }
        elseif ($stateid) {
            $message = ChatMessage::where('state_id', $stateid)->first();
        }


        if (! $message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found',
            ], 404);
        }

        // Map status dari Fonnte ke status internal
        // Gunakan "state" sebagai primary indicator karena selalu ada
        $finalStatus = $state ?? $status;

        $statusMap = [
            'sent' => ['status' => 'sent', 'delivery_status' => 'sent'],
            'delivered' => ['status' => 'delivered', 'delivery_status' => 'delivered'],
            'read' => ['status' => 'read', 'delivery_status' => 'read'],
            'failed' => ['status' => 'failed', 'delivery_status' => 'failed'],
        ];

        if (isset($statusMap[$finalStatus])) {
            $message->update($statusMap[$finalStatus]);

            // Broadcast status update ke frontend via WebSocket
            broadcast(new \App\Events\Chat\MessageUpdated($message->fresh()));

            SystemLogService::record(
                event: 'message_status_updated',
                entityType: 'chat_message',
                entityId: $message->id,
                meta: [
                    'wa_message_id' => $id,
                    'old_status' => $message->status,
                    'new_status' => $status,
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    /**
     * ===============================
     * NORMALIZE PHONE
     * ===============================
     */
    protected function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($clean, '+')) {
            return $clean;
        }
        if (str_starts_with($clean, '0')) {
            return '+62'.substr($clean, 1);
        }
        if (str_starts_with($clean, '62')) {
            return '+'.$clean;
        }

        return '+'.$clean;
    }
}
