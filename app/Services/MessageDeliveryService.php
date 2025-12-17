<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageDeliveryService
{
    const MAX_RETRY = 3;

    /**
     * Kirim pesan WhatsApp + tracking delivery
     */
    public static function send(ChatMessage $message): bool
    {
        try {
            // Tandai sedang dikirim
            $message->update([
                'delivery_status' => 'sending',
            ]);

            /** @var FonnteService $fonnte */
            $fonnte = app(FonnteService::class);

            // ============================
            // KIRIM PESAN SESUAI TIPE
            // ============================
            if ($message->media_url) {
                // Media message
                $result = $fonnte->sendMedia(
                    $message->session->customer->phone,
                    $message->message ?? '',
                    $message->media_url
                );
            } else {
                // Text message
                $result = $fonnte->sendText(
                    $message->session->customer->phone,
                    $message->message
                );
            }

            // ============================
            // SUKSES KIRIM
            // ============================
            $message->update([
                'delivery_status' => 'sent',
                'wa_message_id'   => $result['id'] ?? null,
                'status'          => 'sent',
                'last_error'      => null,
            ]);

            return true;

        } catch (Throwable $e) {

            Log::error('WA send failed', [
                'chat_message_id' => $message->id,
                'retry'           => $message->retry_count,
                'error'           => $e->getMessage(),
            ]);

            self::handleFailure($message, $e->getMessage());
            return false;
        }
    }

    /**
     * Handle retry & failed_final
     */
    protected static function handleFailure(ChatMessage $message, string $error): void
    {
        $nextRetry = $message->retry_count + 1;

        if ($nextRetry >= self::MAX_RETRY) {
            $message->update([
                'delivery_status' => 'failed_final',
                'status'          => 'failed',
                'retry_count'     => $nextRetry,
                'last_error'      => $error,
            ]);
            return;
        }

        $message->update([
            'delivery_status' => 'failed',
            'retry_count'     => $nextRetry,
            'last_retry_at'   => now(),
            'last_error'      => $error,
        ]);
    }
}
