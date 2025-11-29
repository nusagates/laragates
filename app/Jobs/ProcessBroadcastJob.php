<?php

namespace App\Jobs;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Services\WabaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Job hanya 1x attempt, biar tidak spam WA */
    public int $tries = 1;

    public BroadcastCampaign $campaign;

    public function __construct(BroadcastCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Unique job lock — per campaign.
     */
    public function uniqueId(): string
    {
        return 'broadcast_campaign_' . $this->campaign->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load fresh model + template
        $campaign = BroadcastCampaign::with('template')
            ->find($this->campaign->id);

        if (!$campaign) {
            return;
        }

        // Only send if allowed
        if (!in_array($campaign->status, ['approved', 'running', 'scheduled'])) {
            return;
        }

        // Mark running
        if ($campaign->status !== 'running') {
            $campaign->update(['status' => 'running']);
        }

        $template  = $campaign->template;

        // Detect number of variables if not saved
        $requiredVars = $template->body_params_count
            ?? $this->detectVariables($template->body);

        // Save count (cache)
        if (!$template->body_params_count) {
            $template->update(['body_params_count' => $requiredVars]);
        }

        // Take all pending targets
        $targets = BroadcastTarget::where('broadcast_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->get();

        foreach ($targets as $target) {

            try {

                // Build body with variable replacement
                $bodyMessage = $this->replaceVariables(
                    $template->body,
                    $target->variables,
                    $requiredVars
                );

                // Call WABA service
                $response = app(WabaService::class)->sendTemplateMessage(
                    phone:        $target->phone,
                    templateName: $template->name,
                    language:     $template->language,
                    header:       $template->header,
                    bodyMessage:  $bodyMessage,
                    variables:    $target->variables,
                    templateId:   $template->remote_id
                );

                // If 429 Rate Limit → slow down
                if (isset($response['error']['code']) && $response['error']['code'] == 429) {
                    sleep(2); // small delay
                }

                // Guarantee array
                $responseArr = is_array($response) ? $response : ['raw' => $response];

                $waId = $responseArr['messages'][0]['id'] ?? null;

                // Success update
                $target->update([
                    'status'        => 'sent',
                    'wa_message_id' => $waId,
                    'sent_at'       => now(),
                    'response_log'  => $responseArr,
                ]);

                $campaign->increment('sent_count');

            } catch (Throwable $e) {

                $target->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'response_log'  => ['exception' => $e->getMessage()],
                ]);

                $campaign->increment('failed_count');
            }
        }

        // Finish if no pending left
        $left = BroadcastTarget::where('broadcast_campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->count();

        if ($left === 0) {
            $campaign->update(['status' => 'done']);
        }
    }


    /**
     * Replace {{1}}, {{2}} using DB variables
     */
    private function replaceVariables(string $text, ?array $vars, int $requiredVars): string
    {
        if (!$vars || !is_array($vars)) {
            return preg_replace('/\{\{\d+\}\}/', '', $text);
        }

        for ($i = 1; $i <= $requiredVars; $i++) {
            $index = $i - 1;
            $value = $vars[$index] ?? '';
            $text  = str_replace('{{' . $i . '}}', $value, $text);
        }

        return $text;
    }

    /**
     * Count variables inside body text
     */
    private function detectVariables(string $text): int
    {
        preg_match_all('/\{\{\d+\}\}/', $text, $m);
        return count($m[0]);
    }
}
