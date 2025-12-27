<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChatSession;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class DummyChatSessionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /**
             * ===============================
             * CREATE / GET DUMMY CUSTOMER
             * ===============================
             */
            $customer = Customer::firstOrCreate(
                [
                    'phone' => '628999000000', // pastikan unique key sesuai schema kamu
                ],
                [
                    'name'  => 'Dummy Customer Seeder',
                ]
            );

            /**
             * ===============================
             * CREATE DUMMY CHAT SESSIONS
             * ===============================
             */
            for ($i = 1; $i <= 5; $i++) {
                ChatSession::create([
                    'customer_id' => $customer->id, // ⬅️ VALID FK
                    'assigned_to' => null,
                    'status'      => 'open',
                    'priority'    => 'normal',
                    'pinned'      => false,
                    'is_handover' => false,
                    'bot_state'   => null,
                    'bot_context' => null,
                    'created_at'  => now()->subMinutes(rand(5, 180)),
                    'updated_at'  => now(),
                ]);
            }
        });
    }
}
