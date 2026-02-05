<?php

namespace App\Services\Whatsapp\Drivers;

use App\Services\Whatsapp\WhatsappDriverInterface;
use Illuminate\Support\Facades\Http;

class WazapbroDriver implements WhatsappDriverInterface
{
    protected string $endpoint;
    protected string $token;

    public function __construct()
    {
        $this->endpoint = config('services.wazapbro.endpoint');
        $this->token    = config('services.wazapbro.token');
    }

    protected function request(array $payload): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post($this->endpoint, $payload);

        return $response->successful();
    }

    public function sendText(string $phone, string $message): bool
    {
        return $this->request([
            'phone' => $phone,
            'type'  => 'text',
            'text'  => $message,
        ]);
    }

    public function sendMedia(string $phone, string $mediaUrl, ?string $caption = null): bool
    {
        return $this->request([
            'phone'   => $phone,
            'type'    => 'image',
            'url'     => $mediaUrl,
            'caption' => $caption,
        ]);
    }

    public function sendTemplate(string $phone, string $templateName, array $params = []): bool
    {
        return $this->request([
            'phone'    => $phone,
            'type'     => 'template',
            'template' => $templateName,
            'params'   => $params,
        ]);
    }
}
