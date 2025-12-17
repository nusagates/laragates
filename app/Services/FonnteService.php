<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Exception;

class FonnteService
{
    protected string $token;
    protected string $endpoint;

    public function __construct()
    {
        $this->token    = env('FONNTE_TOKEN');
        $this->endpoint = env('FONNTE_SEND_URL', 'https://api.fonnte.com/send');
    }

    /**
     * ============================
     * SEND TEXT MESSAGE
     * ============================
     */
    public function sendText(string $phone, string $message): array
    {
        return $this->send([
            'target'      => $phone,
            'message'     => $message,
            'countryCode' => '62',
        ]);
    }

    /**
     * ============================
     * SEND MEDIA MESSAGE
     * ============================
     */
    public function sendMedia(string $phone, string $caption, string $fileUrl): array
    {
        return $this->send([
            'target'      => $phone,
            'message'     => $caption,
            'url'         => $fileUrl,
            'countryCode' => '62',
        ]);
    }

    /**
     * ============================
     * CORE SEND HANDLER
     * ============================
     */
    protected function send(array $payload): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $this->token,
                ])
                ->asMultipart()
                ->post($this->endpoint, $payload)
                ->throw(); // ⬅️ PENTING

            $json = $response->json();

            // Validasi response Fonnte
            if (! is_array($json) || ! ($json['status'] ?? false)) {
                throw new Exception(
                    'Fonnte API error: ' . json_encode($json)
                );
            }

            return $json;

        } catch (RequestException $e) {
            throw new Exception(
                'Fonnte HTTP error: ' . $e->getMessage()
            );
        }
    }
}
