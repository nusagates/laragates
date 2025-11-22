<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WabaWebhookController extends Controller
{
    // GET: verifikasi webhook dari Meta
    public function verify(Request $request)
    {
        $verifyToken = env('WABA_VERIFY_TOKEN');

        $mode      = $request->get('hub_mode');
        $token     = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

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

        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return response()->json(['status' => 'ignored']);
        }

        $value   = $data['entry'][0]['changes'][0]['value'];
        $message = $value['messages'][0];
        $contact = $value['contacts'][0];

        $phone = $message['from'];
        $waMessageId = $message['id'];
        $text = $message['text']['body'] ?? null;

        // 1) Find/create customer
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $contact['profile']['name'] ?? $phone]
        );

        // 2) Find/create open ticket (pending or ongoing)
        $ticket = Ticket::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'status'      => 'pending'
            ],
            [
                'subject' => 'New WhatsApp Conversation'
            ]
        );

        // 3) Save message into TICKET_MESSAGES
        TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'customer',
            'sender_id'   => null,
            'message'     => $text,
        ]);

        // 4) Update last activity
        $ticket->update(['last_message_at' => now()]);
        $customer->update(['last_message_at' => now()]);

        // TODO (next step): broadcast realtime
        return response()->json(['status' => 'ok']);
    }
}
