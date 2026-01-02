<?php

namespace App\Services\Whatsapp;

interface WhatsappDriverInterface
{
    public function sendText(string $phone, string $message): bool;

    public function sendMedia(
        string $phone,
        string $mediaUrl,
        ?string $caption = null
    ): bool;

    public function sendTemplate(
        string $phone,
        string $templateName,
        array $params = []
    ): bool;
}
