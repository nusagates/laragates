<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class WabaInboundService
{
    /**
     * Handle payload webhook WhatsApp Cloud API (Meta)
     */
    public static function handle(array $payload): void
    {
        // 1. Ambil entry & message dari payload Meta
        $entry   = $payload['entry'][0] ?? null;
        $change  = $entry['changes'][0] ?? null;
        $value   = $change['value'] ?? null;
        $messages = $value['messages'] ?? null;
        $contacts = $value['contacts'] ?? null;

        if (!$messages || !$contacts) {
            // Tidak ada message masuk (bisa status, delivery, dsb) → abaikan
            return;
        }

        $msg     = $messages[0];
        $contact = $contacts[0];

        // Hanya handle text message dulu
        if (($msg['type'] ?? null) !== 'text') {
            return;
        }

        $from      = $msg['from'] ?? null; // nomor WA full, contoh: 6281234567890
        $text      = $msg['text']['body'] ?? '';
        $timestamp = isset($msg['timestamp']) ? (int) $msg['timestamp'] : null;

        if (!$from || !$text) {
            return;
        }

        $createdAt = $timestamp
            ? Carbon::createFromTimestamp($timestamp)->setTimezone(config('app.timezone'))
            : now();

        // 2. Simpan / update Customer
        $customerName  = $contact['profile']['name'] ?? $from;
        $normalizedPhone = self::normalizePhone($from);

        $customer = Customer::firstOrCreate(
            ['phone' => $normalizedPhone],
            ['name'  => $customerName]
        );

        // Update last message time
        $customer->last_message_at = $createdAt;
        $customer->save();

        // 3. Cari / buat ChatSession (BIAR CHAT PANEL TETAP JALAN)
        $session = ChatSession::where('customer_id', $customer->id)
            ->where('status', 'open')
            ->latest('updated_at')
            ->first();

        if (! $session) {
            $session = ChatSession::create([
                'customer_id' => $customer->id,
                'status'      => 'open',
            ]);
        }

        // 4. Simpan ChatMessage (untuk menu Chat)
        $chatMsg = ChatMessage::create([
            'chat_session_id' => $session->id,
            'sender'          => 'customer',
            'user_id'         => null,
            'message'         => $text,
            'type'            => 'text',
            'created_at'      => $createdAt,
            'updated_at'      => $createdAt,
        ]);

        // Update session timestamp
        $session->updated_at = $createdAt;
        $session->save();

        // (Optional) Broadcast ke panel chat realtime kalau sudah pakai event MessageSent
        try {
            broadcast(new \App\Events\MessageSent($chatMsg))->toOthers();
        } catch (\Throwable $e) {
            // kalau gagal broadcast, biarin aja, jangan bikin webhook error
        }

        // 5. Sinkron ke Tickets
        self::syncToTicket($customer, $session, $text, $createdAt);
    }

    /**
     * Buat / update Ticket dan TicketMessage berdasarkan pesan WA masuk.
     */
    protected static function syncToTicket(Customer $customer, ChatSession $session, string $text, Carbon $createdAt): void
    {
        // Cari ticket existing untuk nomor ini yang belum closed
        $ticket = Ticket::where('customer_phone', $customer->phone)
            ->whereIn('status', ['pending', 'ongoing'])
            ->latest('last_message_at')
            ->first();

        if (! $ticket) {
            // Buat ticket baru
            $ticket = Ticket::create([
                'customer_name'   => $customer->name ?? $customer->phone,
                'customer_phone'  => $customer->phone,
                'subject'         => Str::limit($text, 80, '...') ?: 'WhatsApp Conversation',
                'status'          => 'pending',
                'priority'        => 'medium',
                'channel'         => 'whatsapp',
                'assigned_to'     => null,
                'last_message_at' => $createdAt,
            ]);

            // (optional) tiket baru → bisa kita kasih message system
            TicketMessage::create([
                'ticket_id'   => $ticket->id,
                'sender_type' => 'system',
                'user_id'     => null,
                'sender_name' => 'System',
                'message'     => 'Ticket created from WhatsApp conversation.',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
        } else {
            // Update last message time kalau tiket lama
            $ticket->last_message_at = $createdAt;
            $ticket->save();
        }

        // Tambahkan message customer ke ticket_messages
        TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_type' => 'customer',
            'user_id'     => null,
            'sender_name' => $customer->name ?? $customer->phone,
            'message'     => $text,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ]);

        // (Optional) Broadcast event ke halaman Tickets kalau mau realtime
        try {
            broadcast(new \App\Events\TicketMessageSent($ticket->id))->toOthers();
        } catch (\Throwable $e) {
            // diam saja
        }
    }

    /**
     * Normalisasi nomor HP (simple).
     */
    protected static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/\D+/', '', $phone); // buang non digit

        if (Str::startsWith($p, '0')) {
            $p = '62' . substr($p, 1);
        }

        return $p;
    }
}
