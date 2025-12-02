<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FonnteService
{
    protected string $token;

    public function __construct()
    {
        $this->token = env('FONNTE_TOKEN');
    }

    /**
     * Send Text Message
     */
    public function sendText(string $phone, string $message)
    {
        return Http::withHeaders([
            'Authorization' => $this->token
        ])
        ->asMultipart()
        ->post(env('FONNTE_SEND_URL', 'https://api.fonnte.com/send'), [
            'target'      => $phone,
            'message'     => $message,
            'countryCode' => '62',
        ])
        ->json();
    }

    /**
     * Send Media Message
     */
    public function sendMedia(string $phone, string $caption, string $fileUrl)
    {
        return Http::withHeaders([
            'Authorization' => $this->token
        ])
        ->asMultipart()
        ->post(env('FONNTE_SEND_URL', 'https://api.fonnte.com/send'), [
            'target'      => $phone,
            'message'     => $caption,
            'url'         => $fileUrl,
            'countryCode' => '62',
        ])
        ->json();
    }
}
