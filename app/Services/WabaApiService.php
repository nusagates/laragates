<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WabaApiService
{
    protected string $baseUrl;
    protected string $version;
    protected ?string $phoneNumberId;
    protected ?string $accessToken;

    public function __construct()
    {
        // Semua baca dari config/services.php
        $this->baseUrl       = rtrim(config('services.waba.base_url'), '/');
        $this->version       = config('services.waba.version', 'v22.0');
        $this->phoneNumberId = config('services.waba.phone_id');   // FIXED
        $this->accessToken   = config('services.waba.token');      // FIXED
    }

    protected function client()
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->asJson();
    }

    protected function url(string $endpoint): string
    {
        return "{$this->baseUrl}/{$this->version}/{$this->phoneNumberId}/{$endpoint}";
    }

    /**
     * Send simple text message
     */
    public function sendText(string $to, string $message): array
    {
        // MOCK MODE jika credential kosong
        if (!$this->accessToken || !$this->phoneNumberId) {
            $fakeId = 'MOCK-' . Str::uuid()->toString();

            Log::info('[WABA MOCK] sendText', [
                'to'      => $to,
                'message' => $message,
                'id'      => $fakeId,
            ]);

            return [
                'mock' => true,
                'messages' => [
                    ['id' => $fakeId],
                ],
            ];
        }

        // REAL WA CLOUD API
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'body' => $message,
            ],
        ];

        $response = $this->client()->post($this->url('messages'), $payload);

        // Log jika error
        if (!$response->successful()) {
            Log::error('[WABA ERROR SEND TEXT]', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
        }

        return $response->json();
    }
}
