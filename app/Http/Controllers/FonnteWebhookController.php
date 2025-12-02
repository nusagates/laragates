<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;

class FonnteWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Log raw payload (UNTUK DEBUG)
        \Log::info('Fonnte inbound:', $request->all());

        // Fonnte payload langsung di root, bukan dalam "data"
        $sender  = $request->input('sender');    // nomor pengirim
        $message = $request->input('message');   // isi pesan
        $name    = $request->input('name');      // optional
        $type    = $request->input('type');      // text / image / dsb

        // Validasi minimal
        if (!$sender || !$message) {
            return response()->json(['ignored' => true]);
        }

        // NORMALISASI NOMOR
        $phone = preg_replace('/[^0-9]/', '', $sender);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        // CUSTOMER
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone]
        );

        // SESSION
        $session = ChatSession::firstOrCreate(
            ['customer_id' => $customer->id, 'status' => 'open'],
            ['assigned_to' => null]
        );

        // SIMPAN PESAN
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $message,
            'type'            => 'text',
            'is_outgoing'     => false,
            'status'          => 'received'
        ]);

        $session->touch();

        // REALTIME KE UI
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json(['success' => true]);
    }
}
