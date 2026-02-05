<?php

namespace App\Services\Whatsapp\Drivers;

use App\Services\Whatsapp\WhatsappDriverInterface;
use Illuminate\Support\Facades\Http;

class FonnteDriver implements WhatsappDriverInterface
{
    protected string $endpoint;
    protected string $token;

    public function __construct()
    {
        $this->endpoint = config('services.fonnte.endpoint');
        $this->token    = config('services.fonnte.token');
    }

    public function sendText(string $phone, string $message): bool
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->post($this->endpoint, [
            'target'  => $phone,
            'message' => $message,
        ]);

        return $response->successful();
    }

    public function sendMedia(string $phone, string $mediaUrl, ?string $caption = null): bool
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->post($this->endpoint, [
            'target'  => $phone,
            'url'     => $mediaUrl,
            'message' => $caption ?? '',
        ]);

        return $response->successful();
    }

    public function sendTemplate(string $phone, string $templateName, array $params = []): bool
    {
        // Fonnte tidak native template → fallback text
        $text = vsprintf($templateName, $params);

        return $this->sendText($phone, $text);
    }
}
