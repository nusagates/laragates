<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\ChatAgentRouter;

class WabaWebhookController extends Controller
{
    protected ChatAgentRouter $router;

    public function __construct(ChatAgentRouter $router)
    {
        $this->router = $router;
    }

    /**
     * WhatsApp verify webhook challenge (GET)
     */
    public function verify(Request $request)
    {
        $hub_challenge = $request->get('hub_challenge');
        return $hub_challenge ? response($hub_challenge, 200) : response('ok', 200);
    }

    /**
     * Receive inbound message (POST)
     */
    public function receive(Request $request)
    {
        // Ambil data inbound dari gateway WhatsApp
        // ❗❗ Sesuaikan field jika berbeda (sementara buat standard)
        $phone = $request->input('phone');
        $text  = $request->input('text');
        $intent = $request->input('intent'); // opsional (buat NLP nanti)

        if (!$phone || !$text) {
            return response()->json(['error' => 'Invalid WhatsApp payload'], 422);
        }

        // 1️⃣ Cari / buat customer
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $phone]
        );

        // 2️⃣ Cari session yang masih open
        $session = ChatSession::where('customer_id', $customer->id)
                              ->where('status', 'open')
                              ->first();

        // 3️⃣ Jika belum ada → buat session baru
        if (!$session) {
            $session = ChatSession::create([
                'customer_id' => $customer->id,
                'status'      => 'pending', // akan diubah router jadi open/assigned
                'priority'    => 'normal',
                'pinned'      => false,
            ]);

            // Auto route ke agent terbaik
            $this->router->assignSession($session, $intent);
        }

        // 4️⃣ Simpan pesan
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $text,
            'type'            => 'text',
            'status'          => 'received'
        ]);

        // 5️⃣ Response ke gateway (WA API)
        return response()->json([
            'status'       => 'success',
            'session_id'   => $session->id,
            'assigned_to'  => $session->assigned_to,
            'session_status' => $session->status,
        ]);
    }
}
