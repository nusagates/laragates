<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Models\WhatsappTemplate;

class BroadcastDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ID user pertama (biar aman)
        $adminId = \App\Models\User::first()?->id;

        // Create a demo WhatsApp template first
        $template = WhatsappTemplate::create([
            'name' => 'promo_akhir_tahun',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'approved',
            'body' => 'Halo {{1}}, dapatkan diskon hingga 50% untuk pembelian Anda!',
            'body_params_count' => 1,
            'created_by' => $adminId,
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);

        $campaign = BroadcastCampaign::create([
            'name' => 'Promo Akhir Tahun',
            'whatsapp_template_id' => $template->id,
            'audience_type' => 'upload',
            'total_targets' => 10,
            'send_now' => 1,
            'status' => 'done',
            'created_by' => $adminId,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'sent_count' => 7,
            'failed_count' => 3,
            'meta' => [
                'note' => 'Demo data untuk halaman report',
            ],
        ]);

        for ($i = 1; $i <= 10; $i++) {
            BroadcastTarget::create([
                'broadcast_campaign_id' => $campaign->id,
                'phone' => '62812345000'.$i,
                'variables' => json_encode(['name' => 'User '.$i]),
                'status' => $i <= 7 ? 'sent' : 'failed',
                'error_message' => $i <= 7 ? null : 'No WhatsApp account',
            ]);
        }
    }
}
