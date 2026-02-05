<?php

namespace App\Services;

use App\Models\BroadcastTarget;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastFonnteService
{
    protected string $token;

    protected string $endpoint;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN');
        $this->endpoint = env('FONNTE_BASE_URL', 'https://api.fonnte.com').'/send';
    }

    /**
     * Send broadcast message to target using WhatsApp template
     */
    public function sendBroadcastMessage(BroadcastTarget $target): array
    {
        $campaign = $target->campaign;
        $template = $campaign->template;

        if (! $template) {
            throw new Exception('Template not found for campaign');
        }

        // Parse variables - handle both JSON string and array
        $variables = $target->variables;
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?? [];
        } elseif (! is_array($variables)) {
            $variables = [];
        }

        // Build message dari template body dengan replace variables
        $message = $this->buildMessageFromTemplate($template->body, $variables);

        // Send via Fonnte
        try {
            $payload = [
                'target' => $target->phone,
                'message' => $message,
                'countryCode' => '62',
            ];

            Log::info('Sending broadcast via Fonnte', [
                'target_id' => $target->id,
                'phone' => $target->phone,
                'campaign_id' => $campaign->id,
                'template_name' => $template->name,
            ]);

            /** @var FonnteService $fonnte */
            $json = app(FonnteService::class)->sendText(
                $target->phone,
                $message
            );

            // Validasi response Fonnte
            if (! is_array($json) || ! ($json['status'] ?? false)) {
                throw new Exception(
                    'Fonnte API error: '.json_encode($json)
                );
            }

            // update $target
            $target->update([
                'wa_message_id' => is_array($json['id']) && count($json['id']) > 0 ? $json['id'][0] : null,
                'status' => $json['process'],
            ]);

            Log::info('Broadcast sent successfully via Fonnte', [
                'target_id' => $target->id,
                'response' => $json,
            ]);

            return $json;

        } catch (\Throwable $e) {
            Log::error('Failed to send broadcast via Fonnte', [
                'target_id' => $target->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Build message from template body by replacing variables
     * Supports both {{1}}, {{2}} format and associative arrays
     */
    protected function buildMessageFromTemplate(string $templateBody, array $variables): string
    {
        if (empty($variables)) {
            return $templateBody;
        }

        $message = $templateBody;

        // Handle associative array {"kota":"Semarang", "profesi":"Sales"}
        if (array_keys($variables) !== range(0, count($variables) - 1)) {
            // Replace {{variableName}} with values
            foreach ($variables as $key => $value) {
                $message = str_replace("{{{$key}}}", $value, $message);
            }
        }
        // Handle indexed array ["value1", "value2"] with {{1}}, {{2}} format
        else {
            foreach ($variables as $index => $value) {
                $placeholder = '{{'.(string) ($index + 1).'}}';
                $message = str_replace($placeholder, $value, $message);
            }
        }

        return $message;
    }

    /**
     * Send simple text message (for testing)
     */
    public function sendText(string $phone, string $message): array
    {
        try {
            $payload = [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ];

            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->asMultipart()
                ->post($this->endpoint, $payload)
                ->throw();

            return $response->json();

        } catch (\Throwable $e) {
            throw new Exception('Fonnte send error: '.$e->getMessage());
        }
    }
}
