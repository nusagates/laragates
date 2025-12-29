<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Services\MenuEngine;
use App\Services\FonnteService;
use App\Services\AgentRouter;
use App\Services\System\FonnteLogService;

class FonnteWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // ===============================
        // LOG RAW WEBHOOK
        // ===============================
        FonnteLogService::log(
            event: 'fonnte_webhook_received',
            phone: $request->input('sender'),
            meta: $request->all()
        );

        $sender  = $request->input('sender');
        $message = $request->input('message');
        $name    = $request->input('name');

        if (!$sender || !$message) {
            return response()->json(['ignored' => true]);
        }

        // ===============================
        // NORMALISASI NOMOR
        // ===============================
        $phone = preg_replace('/[^0-9]/', '', $sender);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        // ===============================
        // CUSTOMER
        // ===============================
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone]
        );

        // ===============================
        // CHAT SESSION
        // ===============================
        $session = ChatSession::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'pending'])
            ->first();

        if (!$session) {
            $session = ChatSession::create([
                'customer_id' => $customer->id,
                'status'      => 'open',
            ]);
        }

        // ===============================
        // SIMPAN PESAN MASUK
        // ===============================
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $message,
            'type'            => 'text',
            'is_outgoing'     => false,
            'status'          => 'received',
            'is_handover'     => (bool) $session->is_handover,
        ]);

        $session->touch();

        // ===============================
        // LOG INBOUND MESSAGE
        // ===============================
        FonnteLogService::log(
            event: 'fonnte_inbound_message',
            phone: $phone,
            sessionId: $session->id,
            meta: [
                'message_id' => $msg->id,
                'text'       => $message,
                'handover'   => $session->is_handover,
            ]
        );

        // ===============================
        // REALTIME UI
        // ===============================
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

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
                'status'      => 'open',
                'bot_state'   => null,
                'bot_context' => null
            ]);

            $menuText = MenuEngine::mainMenu();
            FonnteService::send($phone, $menuText);

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender'          => 'system',
                'message'         => $menuText,
                'type'            => 'text',
                'is_outgoing'     => true,
                'status'          => 'sent'
            ]);

            return response()->json(['success' => true]);
        }

        // ===============================
        // MENU ENGINE
        // ===============================
        $key = MenuEngine::normalizeKey($text);

        if ($key !== null) {

            $menu = MenuEngine::findByKey($key);

            if (!$menu) {
                return response()->json(['success' => true]);
            }

            if ($menu['action_type'] === 'handover') {

                $agentId = AgentRouter::assignToSession($session);

                $session->update([
                    'is_handover' => true,
                    'bot_state'   => null,
                    'bot_context' => null
                ]);

                $reply =
                    "👩‍💼 *Menghubungkan ke Customer Service*\n\n" .
                    "Mohon tunggu, agent kami akan segera membantu Anda 🙏";

                FonnteService::send($phone, $reply);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender'          => 'system',
                    'message'         => $reply,
                    'type'            => 'text',
                    'is_outgoing'     => true,
                    'status'          => 'sent'
                ]);

                return response()->json([
                    'handover'    => true,
                    'assigned_to' => $agentId
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
