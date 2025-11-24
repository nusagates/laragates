<?php

namespace App\Jobs;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendBroadcastCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BroadcastCampaign $campaign;

    public function __construct(BroadcastCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle(): void
    {
        $campaign = $this->campaign->fresh('template');

        if (! $campaign || ! $campaign->template) {
            Log::warning('Broadcast campaign or template missing', ['id' => $campaign?->id]);
            return;
        }

        $campaign->update(['status' => 'running']);

        $phoneNumberId = env('WABA_PHONE_NUMBER_ID');
        $accessToken   = env('WABA_ACCESS_TOKEN');
        $apiVersion    = env('WABA_API_VERSION', 'v21.0');

        $endpoint = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $sent = 0;
        $failed = 0;

        /** @var BroadcastTarget $target */
        foreach ($campaign->targets()->where('status', 'pending')->cursor() as $target) {

            try {
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to'                => $target->phone,
                    'type'              => 'template',
                    'template'          => [
                        'name'     => $campaign->template->name,
                        'language' => ['code' => $campaign->template->language ?? 'id'],
                        // TODO: kalau mau pakai variable, isi components di sini
                    ],
                ];

                $response = Http::withToken($accessToken)->post($endpoint, $payload);

                if ($response->successful()) {
                    $body = $response->json();

                    $target->update([
                        'status'        => 'sent',
                        'wa_message_id' => $body['messages'][0]['id'] ?? null,
                        'error_message' => null,
                    ]);

                    $sent++;
                } else {
                    $target->update([
                        'status'        => 'failed',
                        'error_message' => $response->body(),
                    ]);
                    $failed++;
                }
            } catch (\Throwable $e) {
                Log::error('Broadcast send error', [
                    'campaign_id' => $campaign->id,
                    'target_id'   => $target->id,
                    'error'       => $e->getMessage(),
                ]);

                $target->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $campaign->update([
            'status'       => 'finished',
            'sent_count'   => $campaign->sent_count + $sent,
            'failed_count' => $campaign->failed_count + $failed,
        ]);
    }
}
