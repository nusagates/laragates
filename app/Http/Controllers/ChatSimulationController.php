<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Customer;
use Illuminate\Http\Request;

class ChatSimulationController extends Controller
{
    public function simulate(Request $request)
    {
        $data = $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string',
        ]);

        // Normalisasi nomor
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Cari customer atau buat baru
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $phone]
        );

        // Cari session yang masih open
        $session = ChatSession::firstOrCreate(
            ['customer_id' => $customer->id, 'status' => 'open'],
            ['assigned_to' => null]
        );

        // Masukkan pesan inbound
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $data['message'],
            'type'            => 'text',
            'is_outgoing'     => false,
        ]);

        $session->touch();

        // Broadcast realtime (ignore error jika pusher off)
        try {
            broadcast(new \App\Events\Chat\MessageSent($msg))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'message' => 'Simulated inbound delivered successfully!'
        ]);
    }
}
