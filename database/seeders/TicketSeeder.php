<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        Ticket::insert([
            [
                'customer_name'  => 'John Doe',
                'customer_phone' => '+628123456789',
                'subject'        => 'Pesanan belum diterima',
                'status'         => 'pending',
                'priority'       => 'high',
                'channel'        => 'whatsapp',
                'assigned_to'    => null,
                'last_message_at'=> Carbon::now()->subMinutes(5),
                'created_at'     => Carbon::now()->subHours(1),
            ],
            [
                'customer_name'  => 'Aldi Widadkd',
                'customer_phone' => '+628987654321',
                'subject'        => 'Minta Invoice Pembayaran',
                'status'         => 'ongoing',
                'priority'       => 'medium',
                'channel'        => 'email',
                'assigned_to'    => 1, // ke Admin pertama
                'last_message_at'=> Carbon::now()->subMinutes(15),
                'created_at'     => Carbon::now()->subHours(2),
            ],
            [
                'customer_name'  => 'Michelle',
                'customer_phone' => '+628555111333',
                'subject'        => 'Barang saya salah',
                'status'         => 'closed',
                'priority'       => 'low',
                'channel'        => 'whatsapp',
                'assigned_to'    => 1,
                'last_message_at'=> Carbon::now()->subDays(1),
                'created_at'     => Carbon::now()->subDays(1),
            ],
        ]);
    }
}
