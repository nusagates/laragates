<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\WaMenu;


class WaMenuSeeder extends Seeder
{
public function run(): void
{
WaMenu::truncate();


WaMenu::insert([
[
'key' => '1',
'title' => 'Informasi Layanan',
'reply_text' => "ℹ️ Informasi Layanan\n\nJam operasional:\nSenin–Jumat | 09.00–17.00 WIB",
'action_type' => 'auto_reply',
'order' => 1,
],
[
'key' => '2',
'title' => 'Cek Status Pesanan / Tiket',
'reply_text' => 'Silakan kirim ID Pesanan / Tiket Anda.\nContoh: ORD12345',
'action_type' => 'ask_input',
'order' => 2,
],
[
'key' => '3',
'title' => 'Keluhan & Bantuan',
'reply_text' => 'Anda akan dihubungkan dengan Customer Service.',
'action_type' => 'handover',
'order' => 3,
],
]);
}
}
