<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Services\MenuEngine;
use App\Services\FonnteService;
use App\Services\AgentRouter;

class FonnteWebhookController extends Controller
{
    public function handle(Request $request)
    {
        /**
         * ===============================
         * LOG RAW PAYLOAD
         * ===============================
         */
        \Log::info('Fonnte inbound:', $request->all());

        $sender  = $request->input('sender');
        $message = $request->input('message');
        $name    = $request->input('name');

        if (! $sender || ! $message) {
            return response()->json(['ignored' => true]);
        }

        /**
         * ===============================
         * NORMALISASI NOMOR
         * ===============================
         */
        $phone = preg_replace('/[^0-9]/', '', $sender);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (! str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        /**
         * ===============================
         * CUSTOMER
         * ===============================
         */
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone]
        );

        /**
         * ===============================
         * CHAT SESSION (OPEN / ACTIVE)
         * ===============================
         */
        $session = ChatSession::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'pending'])
            ->first();

        if (! $session) {
            $session = ChatSession::create([
                'customer_id' => $customer->id,
                'status'      => 'open',
            ]);
        }

        /**
         * ===============================
         * SIMPAN PESAN MASUK
         * ===============================
         */
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

        /**
         * ===============================
         * REALTIME KE UI
         * ===============================
         */
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

        $text = trim(strtolower($message));

        /**
         * ==================================================
         * STEP 4B — HANDOVER GUARD (BOT STOP TOTAL)
         * ==================================================
         */
        if ($session->is_handover) {
            return response()->json([
                'handover' => true,
                'handled'  => true
            ]);
        }

        /**
         * ==================================================
         * STEP 4C — BOT STATE (ASK INPUT)
         * ==================================================
         */
        if ($session->bot_state === 'waiting_order_id') {

            $orderId = strtoupper(trim($message));
            $status  = 'SEDANG DIPROSES'; // TODO: ganti query real

            $reply =
                "📦 *Status Pesanan*\n\n" .
                "ID Pesanan : {$orderId}\n" .
                "Status     : {$status}\n\n" .
                "Ketik *0* untuk kembali ke Menu Utama";

            FonnteService::send($phone, $reply);

            ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender'          => 'system',
                'message'         => $reply,
                'type'            => 'text',
                'is_outgoing'     => true,
                'status'          => 'sent'
            ]);

            $session->update([
                'bot_state'   => null,
                'bot_context' => null
            ]);

            return response()->json(['success' => true]);
        }

        /**
         * ==================================================
         * STEP 2 — MENU UTAMA / RESET
         * ==================================================
         */
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

        /**
         * ==================================================
         * STEP 3 — PILIH MENU / SUBMENU
         * ==================================================
         */
        $key = MenuEngine::normalizeKey($text);

        if ($key !== null) {

            $parentId = null;

            if ($session->bot_context && str_starts_with($session->bot_context, 'menu:')) {
                $parentId = (int) str_replace('menu:', '', $session->bot_context);
            }

            $menu = MenuEngine::findByKey($key, $parentId);

            if (! $menu) {

                $fallback =
                    "Maaf 🙏 pilihan tidak dikenali.\n\n" .
                    MenuEngine::mainMenu();

                FonnteService::send($phone, $fallback);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender'          => 'system',
                    'message'         => $fallback,
                    'type'            => 'text',
                    'is_outgoing'     => true,
                    'status'          => 'sent'
                ]);

                return response()->json(['success' => true]);
            }

            /**
             * ===============================
             * SUBMENU
             * ===============================
             */
            if (MenuEngine::hasChildren($menu['id'])) {

                $submenuText = MenuEngine::subMenu(
                    $menu['id'],
                    $menu['title']
                );

                $session->update([
                    'bot_context' => 'menu:' . $menu['id']
                ]);

                FonnteService::send($phone, $submenuText);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender'          => 'system',
                    'message'         => $submenuText,
                    'type'            => 'text',
                    'is_outgoing'     => true,
                    'status'          => 'sent'
                ]);

                return response()->json(['success' => true]);
            }

            /**
             * ===============================
             * AUTO REPLY
             * ===============================
             */
            if ($menu['action_type'] === 'auto_reply') {

                FonnteService::send($phone, $menu['reply_text']);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender'          => 'system',
                    'message'         => $menu['reply_text'],
                    'type'            => 'text',
                    'is_outgoing'     => true,
                    'status'          => 'sent'
                ]);

                return response()->json(['success' => true]);
            }

            /**
             * ===============================
             * ASK INPUT
             * ===============================
             */
            if ($menu['action_type'] === 'ask_input') {

                $session->update([
                    'bot_state'   => 'waiting_order_id',
                    'bot_context' => 'order_status'
                ]);

                FonnteService::send($phone, $menu['reply_text']);

                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'sender'          => 'system',
                    'message'         => $menu['reply_text'],
                    'type'            => 'text',
                    'is_outgoing'     => true,
                    'status'          => 'sent'
                ]);

                return response()->json(['success' => true]);
            }

            /**
             * ===============================
             * STEP 5 — HANDOVER + AUTO ASSIGN
             * ===============================
             */
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
                    'success'     => true,
                    'handover'    => true,
                    'assigned_to' => $agentId
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
