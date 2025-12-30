<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\SystemLogService;

class ChatSimulationController extends Controller
{
    public function simulate(Request $request)
    {
        $data = $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string',
            'name'    => 'nullable|string',
        ]);

        // ===============================
        // NORMALISASI NOMOR
        // ===============================
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);

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
            ['name' => $data['name'] ?? $phone]
        );

        // ===============================
        // CHAT SESSION
        // ===============================
        $session = ChatSession::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'status'      => 'open',
            ]
        );

        // ===============================
        // CHAT MESSAGE (INBOUND)
        // ===============================
        $msg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'message'         => $data['message'],
            'type'            => 'text',
            'is_outgoing'     => false,
            'status'          => 'received',
        ]);

        $session->touch();

        // ===============================
        // ✅ SYSTEM LOG (COMPLIANCE SAFE)
        // ===============================
        SystemLogService::record(
            event: 'chat_inbound_simulated',
            entityType: 'chat_session',
            entityId: $session->id,
            oldValues: null,
            newValues: null,
            meta: [
                'description' => 'Simulated inbound WhatsApp message',
                'provider'    => 'simulate',
                'phone'       => $phone,
                'message'     => $data['message'],
            ]
        );

        return response()->json([
            'success'    => true,
            'session_id' => $session->id,
            'message'    => 'Simulated inbound delivered & logged',
        ]);
    }
}
