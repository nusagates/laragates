<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;

<<<<<<< HEAD
use App\Services\MenuEngine;
use App\Services\FonnteService;

=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
class FonnteWebhookController extends Controller
{
    public function handle(Request $request)
    {
<<<<<<< HEAD
        // ===============================
        // LOG RAW PAYLOAD
        // ===============================
        \Log::info('Fonnte inbound:', $request->all());

        $sender  = $request->input('sender');
        $message = $request->input('message');
        $name    = $request->input('name');

=======
        // Log raw payload (UNTUK DEBUG)
        \Log::info('Fonnte inbound:', $request->all());

        // Fonnte payload langsung di root, bukan dalam "data"
        $sender  = $request->input('sender');    // nomor pengirim
        $message = $request->input('message');   // isi pesan
        $name    = $request->input('name');      // optional
        $type    = $request->input('type');      // text / image / dsb

        // Validasi minimal
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        if (!$sender || !$message) {
            return response()->json(['ignored' => true]);
        }

<<<<<<< HEAD
        // ===============================
        // NORMALISASI NOMOR
        // ===============================
=======
        // NORMALISASI NOMOR
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        $phone = preg_replace('/[^0-9]/', '', $sender);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

<<<<<<< HEAD
        // ===============================
        // CUSTOMER
        // ===============================
=======
        // CUSTOMER
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone]
        );

<<<<<<< HEAD
        // ===============================
        // SESSION
        // ===============================
=======
        // SESSION
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        $session = ChatSession::firstOrCreate(
            ['customer_id' => $customer->id, 'status' => 'open'],
            ['assigned_to' => null]
        );

<<<<<<< HEAD
        // ===============================
        // SIMPAN PESAN MASUK
        // ===============================
=======
        // SIMPAN PESAN
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $message,
            'type'            => 'text',
            'is_outgoing'     => false,
            'status'          => 'received'
        ]);

        $session->touch();

<<<<<<< HEAD
        // ===============================
        // REALTIME KE UI
        // ===============================
=======
        // REALTIME KE UI
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

<<<<<<< HEAD
        // ===============================
        // NORMALISASI TEXT
        // ===============================
        $text = trim(strtolower($message));

        // ==================================================
        // STEP 4C — HANDLE BOT STATE (ASK INPUT)
        // ==================================================
        if ($session->bot_state === 'waiting_order_id') {

            $orderId = strtoupper($message);

            // 🔧 DUMMY STATUS (nanti ganti query real)
            $status = 'SEDANG DIPROSES';

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

            // 🔄 RESET STATE
            $session->update([
                'bot_state'   => null,
                'bot_context' => null
            ]);

            return response()->json(['success' => true]);
        }

        // ==================================================
        // STEP 2 — AUTO MENU UTAMA
        // ==================================================
        if (in_array($text, ['hi', 'halo', 'menu', '0'])) {

            $menuText = MenuEngine::mainMenu();

            FonnteService::send($phone, $menuText);

            $outMsg = ChatMessage::create([
                'chat_session_id' => $session->id,
                'sender'          => 'system',
                'message'         => $menuText,
                'type'            => 'text',
                'is_outgoing'     => true,
                'status'          => 'sent'
            ]);

            try {
                broadcast(new \App\Events\Chat\MessageSent($outMsg))->toOthers();
            } catch (\Throwable $e) {}

            return response()->json(['success' => true]);
        }

        // ==================================================
        // STEP 3 + STEP 4B — HANDLE INPUT MENU
        // ==================================================
        if (ctype_digit($text)) {

            $menu = MenuEngine::findByKey($text);

            if (!$menu) {

                $fallback = "Maaf 🙏 pilihan tidak dikenali.\n\n" . MenuEngine::mainMenu();

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

            // AUTO REPLY
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

            // ASK INPUT (STEP 4B)
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

            // HANDOVER
            if ($menu['action_type'] === 'handover') {

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
        }

=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        return response()->json(['success' => true]);
    }
}
