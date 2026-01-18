<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Customer;
use App\Services\AgentRouter;
use App\Services\ContactIntelligenceService;
use App\Services\ContactScoringService;
use App\Services\FonnteService;
use App\Services\MenuEngine;
use App\Services\System\ChatLogService;
use App\Services\System\FonnteLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FonnteWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('[FONNTE WEBHOOK RECEIVED]:1', [
            'payload' => $request->all(),
        ]);
        // ===============================
        // LOG RAW WEBHOOK
        // ===============================
        FonnteLogService::log(
            event: 'fonnte_webhook_received',
            phone: $request->input('sender'),
            meta: $request->all()
        );

        $sender = $request->input('sender');
        $message = $request->input('message');
        $name = $request->input('name');

        if (! $sender || ! $message) {
            return response()->json(['ignored' => true]);
        }

        // ===============================
        // NORMALISASI NOMOR
        // ===============================
        $phone = preg_replace('/[^0-9]/', '', $sender);

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }
        if (! str_starts_with($phone, '62')) {
            $phone = '62'.$phone;
        }

        // ===============================
        // CUSTOMER
        // ===============================
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone]
        );

        /**
         * 🚫 BLACKLIST ENFORCEMENT (INBOUND)
         * - stop sebelum buat session / message
         * - tetap log ke system
         * - return 200 (aman dari retry WA)
         */
        if ($customer->is_blacklisted) {

            ChatLogService::log(
                event: 'blacklist_blocked_inbound',
                meta: [
                    'customer_id' => $customer->id,
                    'phone' => $phone,
                    'text' => $message,
                ]
            );

            return response()->json([
                'blocked' => true,
            ]);
        }

        // ===============================
        // CHAT SESSION
        // ===============================
        $session = ChatSession::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'pending'])
            ->first();

        if (! $session) {
            $session = ChatSession::create([
                'customer_id' => $customer->id,
                'status' => 'open',
            ]);

            // stats
            $customer->increment('total_chats');
        }

        ContactIntelligenceService::evaluate($customer);

        // ===============================
        // SIMPAN PESAN MASUK
        // ===============================
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender' => 'customer',
            'message' => $message,
            'type' => 'text',
            'is_outgoing' => false,
            'status' => 'received',
            'is_handover' => (bool) $session->is_handover,
        ]);

        $session->touch();

        // ===============================
        // UPDATE CONTACT STATS
        // ===============================
        $customer->increment('total_messages');
        $customer->update([
            'last_message_at' => now(),
            'last_contacted_at' => now(),
        ]);
        ContactScoringService::evaluate($customer);

        // ===============================
        // LOG INBOUND MESSAGE
        // ===============================
        FonnteLogService::log(
            event: 'fonnte_inbound_message',
            phone: $phone,
            sessionId: $session->id,
            meta: [
                'message_id' => $msg->id,
                'text' => $message,
                'handover' => $session->is_handover,
            ]
        );

        // ===============================
        // REALTIME UI
        // ===============================
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {
        }

        // ===============================
        // HANDOVER GUARD
        // ===============================
        if ($session->is_handover) {
            return response()->json(['handled' => true]);
        }

        $text = trim(strtolower($message));

        // ===============================
        // RESET / MENU
        // ===============================
        if (in_array($text, ['hi', 'halo', 'menu', '0'])) {

            $session->update([
                'is_handover' => false,
                'assigned_to' => null,
                'status' => 'open',
                'bot_state' => null,
                'bot_context' => null,
            ]);

            $menuText = MenuEngine::mainMenu();
            app(FonnteService::class)->sendText($phone, $menuText);

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender' => 'system',
                'message' => $menuText,
                'type' => 'text',
                'is_outgoing' => true,
                'status' => 'sent',
            ]);

            return response()->json(['success' => true]);
        }

        // ===============================
        // MENU ENGINE
        // ===============================
        $key = MenuEngine::normalizeKey($text);

        if ($key !== null) {

            $menu = MenuEngine::findByKey($key);

            if (! $menu) {
                return response()->json(['success' => true]);
            }

            if ($menu['action_type'] === 'handover') {

                $agentId = AgentRouter::assignToSession($session);

                // Check if agent assignment failed
                if (! $agentId) {
                    $reply =
                        "⚠️ *Maaf, Semua Agent Sedang Sibuk*\n\n".
                        'Saat ini tidak ada agent yang tersedia. Silakan coba beberapa saat lagi atau ketik *0* untuk kembali ke menu utama.';

                    app(FonnteService::class)->sendText($phone, $reply);

                    ChatMessage::create([
                        'chat_session_id' => $session->id,
                        'sender' => 'system',
                        'message' => $reply,
                        'type' => 'text',
                        'is_outgoing' => true,
                        'status' => 'sent',
                    ]);

                    return response()->json([
                        'handover' => false,
                        'error' => 'No agent available',
                    ]);
                }

                $session->update([
                    'is_handover' => true,
                    'bot_state' => null,
                    'bot_context' => null,
                ]);

                $reply =
                    "👩‍💼 *Menghubungkan ke Customer Service*\n\n".
                    'Mohon tunggu, agent kami akan segera membantu Anda 🙏';

                app(FonnteService::class)->sendText($phone, $reply);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender' => 'system',
                    'message' => $reply,
                    'type' => 'text',
                    'is_outgoing' => true,
                    'status' => 'sent',
                ]);

                return response()->json([
                    'handover' => true,
                    'assigned_to' => $agentId,
                ]);
            }
        }

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
        Log::info('[FONNTE STATUS UPDATE RECEIVED]:1', [
            'payload' => $request->all(),
        ]);
        try {
            $data = $request->validate([
                'device' => 'required|string',
                'id' => 'nullable|string',  // Optional: only present in "sent" status
                'stateid' => 'nullable|string',
                'status' => 'nullable|string',  // Optional: only present in "sent" status
                'state' => 'nullable|string',  // Present in all statuses
            ]);

            $stateId = $data['stateid'];
            $status = $data['status'] ?? null;
            $state = $data['state'];
            $id = $data['id'] ?? null;
            $device = $data['device'] ?? null;

            if (! $data) {
                throw new \Exception('Invalid JSON payload', 420);
            }

            // Handle state "sent"
            if ($state === 'sent' && $id) {
                $message = ChatMessage::whereIn('wa_message_id', [$id, "[$id]"])->first();
                if ($message) {
                    Log::info('[WEBHOOK] Updating message to sent', [
                        'message_id' => $message->id,
                        'wa_message_id' => $id,
                        'state_id' => $stateId,
                    ]);

                    $message->update([
                        'delivery_status' => 'sent',
                        'wa_message_id' => trim($id, '[]'),
                        'state_id' => $stateId,
                        'status' => 'sent',
                        'last_error' => null,
                    ]);

                    // Refresh message to get updated data
                    $message->refresh();

                    Log::info('[WEBHOOK] Broadcasting MessageUpdated (sent)', [
                        'message_id' => $message->id,
                        'delivery_status' => $message->delivery_status,
                    ]);

                    broadcast(new \App\Events\Chat\MessageUpdated($message));

                } else {
                    Log::warning('Fonnte status update: message not found for state_id '.$stateId);

                    return response()->json(['success' => false, 'message' => 'Message not found'], 404);
                }
            } elseif ($state === 'delivered' || $state === 'read') {
                $message = ChatMessage::where('state_id', $stateId)->first();
                if ($message) {
                    Log::info('[WEBHOOK] Updating message status', [
                        'message_id' => $message->id,
                        'old_status' => $message->delivery_status,
                        'new_status' => $state,
                        'state_id' => $stateId,
                    ]);

                    $message->update([
                        'delivery_status' => $state,
                        'status' => $state,
                    ]);

                    // Refresh message to get updated data
                    $message->refresh();

                    Log::info('[WEBHOOK] Broadcasting MessageUpdated', [
                        'message_id' => $message->id,
                        'delivery_status' => $message->delivery_status,
                        'session_id' => $message->chat_session_id,
                    ]);

                    broadcast(new \App\Events\Chat\MessageUpdated($message));
                } else {
                    Log::warning('Fonnte status update: message not found for state_id '.$stateId);

                    return response()->json(['success' => false, 'message' => 'Message not found'], 404);
                }
            }

            FonnteLogService::log(
                event: 'fonnte_status_'.$state,
                phone: $message->session->customer->phone,
                sessionId: $message->chat_session_id,
                meta: [
                    'message_id' => $message->id,
                    'state_id' => $stateId,
                    'status' => $status,
                    'wa_message_id' => $id,
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            // throw $th;
        }

    }
}
