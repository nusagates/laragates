<?php

namespace App\Jobs;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Services\WabaService; // kita buat nanti
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public BroadcastCampaign $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(BroadcastCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Main handler for sending WhatsApp broadcast.
     */
    public function handle(): void
    {
        // Refresh campaign to get latest status
        $campaign = BroadcastCampaign::with('template')
            ->find($this->campaign->id);

        if (!$campaign) {
            return;
        }

        // If campaign was canceled or rejected mid-way
        if (!in_array($campaign->status, ['approved', 'running', 'scheduled'])) {
            return;
        }

        // Set running
        if ($campaign->status !== 'running') {
            $campaign->status = 'running';
            $campaign->save();
        }

        $template = $campaign->template;
        $requiredVars = $template->body_params_count ?? $this->detectVariables($template->body);

        // Take all pending targets
        $targets = BroadcastTarget::where('broadcast_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->get();

        foreach ($targets as $target) {

            try {
                // Build message text (replace variables)
                $bodyMessage = $this->replaceVariables($template->body, $target->variables, $requiredVars);

                // CALL WABA API
                // (kita buat service wrapper-nya: WabaService)
                $response = app(WabaService::class)->sendTemplateMessage(
                    phone: $target->phone,
                    templateName: $template->name,
                    language: $template->language,
                    header: $template->header,
                    bodyMessage: $bodyMessage,
                    variables: $target->variables,
                    templateId: $template->remote_id
                );

                // Extract WhatsApp message ID
                $waId = $response['messages'][0]['id'] ?? null;

                // Update target success
                $target->update([
                    'status'        => 'sent',
                    'wa_message_id' => $waId,
                    'sent_at'       => now(),
                    'response_log'  => $response,
                ]);

                $campaign->increment('sent_count');

            } catch (Throwable $e) {

                $target->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'response_log'  => ['error' => $e->getMessage()],
                ]);

                $campaign->increment('failed_count');
            }
        }

        // Check if all processed
        $remaining = BroadcastTarget::where('broadcast_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->count();

        if ($remaining === 0) {
            $campaign->status = 'done';
            $campaign->save();
        }
    }

    /**
     * Replace {{1}}, {{2}} with variables from DB
     */
    private function replaceVariables(string $text, ?array $vars, int $requiredVars)
    {
        if (!$vars || !is_array($vars)) return $text;

        for ($i = 1; $i <= $requiredVars; $i++) {

            $key = $i - 1;

            $replaceValue = $vars[$key] ?? '';

            $text = str_replace('{{' . $i . '}}', $replaceValue, $text);
        }

        return $text;
    }

    /**
     * Detect variables inside body ({{1}}, {{2}})
     */
    private function detectVariables(string $text)
    {
        preg_match_all('/\{\{\d+\}\}/', $text, $matches);
        return count($matches[0]);
    }
}
