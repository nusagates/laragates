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
     * Detail messages (/api/chats/{session})
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
     * Send message in chat (/api/chats/{session}/send)
     */
    public function send(Request $request, ChatSession $session)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $agent = Auth::user();
        if (!$agent) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Auto-assign chat to this agent
        if (!$session->assigned_to) {
            $session->update(['assigned_to' => $agent->id]);
        }

        // =======================================================
        // DUMMY MODE (kalau token Meta tidak ada)
        // =======================================================
        if (!env('WA_BUSINESS_TOKEN')) {

            $msg = ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender'          => 'agent',
                'user_id'         => $agent->id,
                'message'         => $request->message,
                'type'            => 'text',
                'status'          => 'sent',
                'is_outgoing'     => true,
            ]);

            $session->touch();

            try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } 
            catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'dummy'   => true,
                'data'    => $msg
            ]);
        }
        // =======================================================


        // REAL MODE (kirim via WA API jika token ada)
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => $agent->id,
            'message'         => $request->message,
            'type'            => 'text',
            'status'          => 'sent',
            'is_outgoing'     => true,
        ]);

        $session->touch();

        try {
            app(WabaApiService::class)->sendText(
                $session->customer->phone,
                $request->message
            );
        } catch (\Throwable $e) {}

        try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } 
        catch (\Throwable $e) {}

        return response()->json(['success' => true, 'data' => $msg]);
    }

    /**
     * Outbound (CS kirim chat pertama)
     */
    public function outbound(Request $request, WabaApiService $waba)
    {
        $data = $request->validate([
            'phone'         => 'required|string|max:30',
            'name'          => 'nullable|string|max:150',
            'message'       => 'required|string|max:4000',
            'create_ticket' => 'sometimes|boolean',
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

        // =======================================================
        // DUMMY MODE
        // =======================================================
        if (!env('WA_BUSINESS_TOKEN')) {

            $msg = ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender'          => 'agent',
                'user_id'         => Auth::id(),
                'message'         => $data['message'],
                'type'            => 'text',
                'status'          => 'sent',
                'is_outgoing'     => true,
            ]);

            $session->touch();

            try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } 
            catch (\Throwable $e) {}

            return response()->json([
                'success'    => true,
                'dummy'      => true,
                'session_id' => $session->id,
            ]);
        }
        // =======================================================


        // REAL MODE
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => Auth::id(),
            'message'         => $data['message'],
            'type'            => 'text',
            'status'          => 'sent',
            'is_outgoing'     => true,
        ]);

        $session->touch();

        try { $waba->sendText($customer->phone, $data['message']); } 
        catch (\Throwable $e) {}

        try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } 
        catch (\Throwable $e) {}

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Assign chat to agent
     */
    public function assign(Request $request, ChatSession $session)
    {
        $data = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
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

    public function updateStatus(Request $request, ChatMessage $message)
{
    $request->validate([
        'status' => 'required|string|in:sent,delivered,read'
    ]);

    $message->status = $request->status;
    $message->save();

    try {
        broadcast(new \App\Events\Chat\MessageUpdated($message))->toOthers();
    } catch (\Throwable $e) {}

    return response()->json(['success' => true]);
}
public function messages(ChatSession $session)
{
    $session->load([
        'messages' => fn ($q) => $q->orderBy('created_at', 'asc'),
    ]);

    return $session->messages->map(function ($m) {
        return [
            'id'          => $m->id,
            'message'     => $m->message,
            'media_url'   => $m->media_url,
            'media_type'  => $m->media_type,
            'created_at'  => $m->created_at->format('H:i'),
            'sender'      => $m->sender,
            'is_outgoing' => $m->sender === 'agent', // 🔥 ini yg penting
        ];
    });
}

public function sendMedia(Request $request, ChatSession $session)
{
    $agent = Auth::user();

    if (!$agent) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $request->validate([
        'message' => 'nullable|string',
        'media'   => 'nullable|file|max:10240', // 10MB
    ]);

    /* ===========================
       DUMMY MODE (TANPA TOKEN)
    ============================ */
    if (!env('WA_BUSINESS_TOKEN')) {

        $mediaUrl = null;
        $mediaType = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->store('chat_media', 'public');

            $mediaUrl = asset('storage/' . $path);
            $mediaType = $file->getMimeType();
        }

        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'agent',
            'user_id'         => $agent->id,
            'message'         => $request->message ?? '',
            'media_url'       => $mediaUrl,
            'media_type'      => $mediaType,
            'type'            => $mediaType ? 'media' : 'text',
            'is_outgoing'     => true,
            'status'          => 'sent',
        ]);

        $session->touch();

        try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } 
        catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'dummy'   => true,
            'data'    => $msg
        ]);
    }

    /* ===========================
       REAL MODE API KALAU ADA
    ============================ */
    // tempatkan kode WA API asli di sini

    return response()->json(['success' => true]);
}




}

