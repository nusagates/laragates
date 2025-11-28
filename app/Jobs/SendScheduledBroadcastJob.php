<?php

namespace App\Jobs;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendScheduledBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Ambil semua campaign yang waktunya sudah tiba
        $campaigns = BroadcastCampaign::where('status', 'scheduled')
            ->where('schedule_type', 'later')
            ->where('send_now', 0)
            ->where('send_at', '<=', now())
            ->get();

        foreach ($campaigns as $campaign) {

            $template = $campaign->template;

            $sent = 0;
            $failed = 0;

            $targets = BroadcastTarget::where('campaign_id', $campaign->id)->get();

            foreach ($targets as $target) {

                try {

                    // ==== PANGGIL FUNGSI KIRIM WHATSAPP ====
                    sendWabaTemplate(
                        phone: $target->phone,
                        template: $template,
                        variables: json_decode($target->variables ?? '[]', true)
                    );

                    $sent++;

                } catch (\Exception $e) {

                    $failed++;
                }
            }

            // Update campaign selesai
            $campaign->update([
                'status' => 'done',
                'sent_count' => $sent,
                'failed_count' => $failed,
            ]);
        }
    }
}
