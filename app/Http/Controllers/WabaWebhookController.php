<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
<<<<<<< HEAD
use App\Models\Menu; // <-- ADD THIS
use App\Http\Controllers\WabaMenuController; // <-- ADD THIS
=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247

class WabaWebhookController extends Controller
{
    public function receiveFonnte(Request $request)
    {
        Log::info('[FONNTE WEBHOOK]', $request->all());

        // Validate secret
        $sentSecret = $request->header('X-Fonnte-Secret') ?? $request->input('secret');
        if ($sentSecret !== env('FONNTE_WEBHOOK_SECRET')) {
            return response()->json(['error' => 'invalid secret'], 401);
        }

        // Payload Fonnte
<<<<<<< HEAD
        $from     = $request->input('from');
        $message  = $request->input('message');
        $msgId    = $request->input('id');
        $buttonId = $request->input('button_id');  // <-- FONNTE SENDS THIS WHEN BUTTON CLICKED

        if (!$from || (!$message && !$buttonId)) {
=======
        $from    = $request->input('from');
        $message = $request->input('message');
        $msgId   = $request->input('id');

        if (!$from || !$message) {
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
            return response()->json(['status' => false, 'reason' => 'invalid payload'], 400);
        }

        // Normalize phone
        $phone = $this->normalizePhone($from);

        // Customer
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $phone]
        );

        // Session
        $session = ChatSession::firstOrCreate(
            ['customer_id' => $customer->id, 'status' => 'open']
        );

        // Save chat message
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
<<<<<<< HEAD
            'message'         => $buttonId ?: $message,
=======
            'message'         => $message,
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
            'type'            => 'text',
            'is_outgoing'     => false,
            'external_id'     => $msgId,
        ]);

        // Update session timestamp
        $session->touch();

<<<<<<< HEAD
        // Realtime broadcast
        try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } catch (\Throwable $e){}


        /**
         * ======================================================
         *  🔥  MENU HANDLER (DINAMIS, MULTI-LEVEL)
         * ======================================================
         * Fonnte mengirim:
         * - $buttonId → ketika user klik tombol
         * - $message → pesan teks biasa
         */

        if ($buttonId) {
            return $this->handleMenuSelection($phone, $buttonId);
        }

        // Kalau user kirim teks "menu" → kirim main menu
        if (strtolower($message) === 'menu') {
            return app(WabaMenuController::class)->sendMainMenu($phone);
        }

        return response()->json(['success' => true]);
    }


    /**
     * ======================================================
     *  🔥  HANDLE MENU SELECTION
     * ======================================================
     */
    private function handleMenuSelection($phone, $payload)
    {
        Log::info("WABA MENU PAYLOAD: $payload");

        $menu = Menu::where('payload', $payload)->first();

        if (!$menu) {
            Log::warning("MENU NOT FOUND FOR PAYLOAD: $payload");
            return response()->json(['menu' => 'not_found']);
        }

        // Jika menu punya anak → kirim submenu
        if ($menu->children()->count() > 0) {
            return app(WabaMenuController::class)->sendSubmenu($phone, $menu);
        }

        // Jika menu adalah child → kirim final response
        return app(WabaMenuController::class)->sendFinal($phone, $menu);
    }


=======
        // Realtime broadcast (jika kamu pakai)
        try { broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers(); } catch (\Throwable $e){}

        return response()->json(['success' => true]);
    }

>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    private function normalizePhone($phone)
    {
        $n = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($n, '0')) return '62' . substr($n, 1);
        if (str_starts_with($n, '62')) return $n;
        return '62' . $n;
    }
}
