<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WabaWebhookController extends Controller
{
    // GET: verifikasi webhook dari Meta
    public function verify(Request $request)
    {
        $verifyToken = env('WABA_VERIFY_TOKEN');

        $mode       = $request->get('hub_mode');
        $token      = $request->get('hub_verify_token');
        $challenge  = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // POST: pesan masuk dari WhatsApp
    public function receive(Request $request)
    {
        $data = $request->all();
        Log::info('WABA Webhook received', $data);

        // Struktur standard Cloud API
        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return response()->json(['status' => 'ignored']);
        }

        $value   = $data['entry'][0]['changes'][0]['value'];
        $message = $value['messages'][0];
        $contact = $value['contacts'][0];

        $fromPhone = $message['from'];               // nomor customer
        $waMsgId   = $message['id'];
        $text      = $message['text']['body'] ?? null;

        // 1. find / create customer
        $customer = Customer::firstOrCreate(
            ['phone' => $fromPhone],
            ['name' => $contact['profile']['name'] ?? null]
        );

        // 2. find / create open session
        $session = ChatSession::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'status'      => 'open',
            ],
            []
        );

        // 3. simpan message
        ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $text,
            'type'            => 'text',
            'wa_message_id'   => $waMsgId,
        ]);

        $customer->update([
            'last_message_at' => now(),
        ]);

        // TODO: broadcast ke frontend (Pusher / Laravel Websockets)
        return response()->json(['status' => 'ok']);
    }
}

