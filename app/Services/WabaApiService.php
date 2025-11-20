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
        $this->baseUrl       = rtrim(config('services.waba.base_url', env('WABA_BASE_URL', 'https://graph.facebook.com')), '/');
        $this->version       = config('services.waba.version', env('WABA_API_VERSION', 'v21.0'));
        $this->phoneNumberId = env('WABA_PHONE_NUMBER_ID');
        $this->accessToken   = env('WABA_ACCESS_TOKEN');
    }

    protected function client()
    {
        return Http::withToken($this->accessToken)->acceptJson()->asJson();
    }

    protected function url(string $endpoint): string
    {
        return "{$this->baseUrl}/{$this->version}/{$this->phoneNumberId}/{$endpoint}";
    }

    /**
     * Send simple text message
     * Auto-uses MOCK if not configured
     */
    public function sendText(string $to, string $message): array
    {
        // 💡 MOCK mode (no credentials)
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

        // 📌 REAL API MODE
        $response = $this->client()->post($this->url('messages'), [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'body' => $message,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('[WABA ERROR]', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
        }

        return $response->json();
    }
}
