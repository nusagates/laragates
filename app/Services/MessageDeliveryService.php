<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Services\System\FonnteLogService;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessageDeliveryService
{
    const MAX_RETRY = 3;

    public static function send(ChatMessage $message): bool
    {
        try {
            // ===============================
            // LOG OUTBOUND ATTEMPT
            // ===============================
            FonnteLogService::log(
                event: 'fonnte_outbound_send',
                phone: $message->session->customer->phone,
                sessionId: $message->chat_session_id,
                meta: [
                    'message_id' => $message->id,
                    'has_media' => (bool) $message->media_url,
                ]
            );

            $message->update([
                'delivery_status' => 'sending',
            ]);

            /** @var FonnteService $fonnte */
            $fonnte = app(FonnteService::class);

            if ($message->media_url) {
                $result = $fonnte->sendMedia(
                    $message->session->customer->phone,
                    $message->message ?? '',
                    $message->media_url
                );
            } else {
                $result = $fonnte->sendText(
                    $message->session->customer->phone,
                    $message->message
                );
            }

            // ===============================
            // SUCCESS
            // ===============================
            $message->update([
                'delivery_status' => 'sent',
                'wa_message_id' => $result['id'] ?? null,
                'status' => 'sent',
                'last_error' => null,
            ]);

            FonnteLogService::log(
                event: 'fonnte_outbound_success',
                phone: $message->session->customer->phone,
                sessionId: $message->chat_session_id,
                meta: [
                    'message_id' => $message->id,
                    'wa_id' => $result['id'] ?? null,
                ]
            );

            // Broadcast status update ke frontend
            broadcast(new \App\Events\Chat\MessageUpdated($message->fresh()))->toOthers();

            return true;

        } catch (Throwable $e) {

            Log::error('WA send failed', [
                'chat_message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            FonnteLogService::log(
                event: 'fonnte_outbound_failed',
                phone: $message->session->customer->phone,
                sessionId: $message->chat_session_id,
                meta: [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                    'retry' => $message->retry_count,
                ]
            );

            self::handleFailure($message, $e->getMessage());

            return false;
        }
    }

    protected static function handleFailure(ChatMessage $message, string $error): void
    {
        $nextRetry = $message->retry_count + 1;

        if ($nextRetry >= self::MAX_RETRY) {
            $message->update([
                'delivery_status' => 'failed_final',
                'status' => 'failed',
                'retry_count' => $nextRetry,
                'last_error' => $error,
            ]);

            // Broadcast failure status ke frontend
            broadcast(new \App\Events\Chat\MessageUpdated($message->fresh()))->toOthers();

            return;
        }

        $message->update([
            'delivery_status' => 'failed',
            'retry_count' => $nextRetry,
            'last_retry_at' => now(),
            'last_error' => $error,
        ]);
    }
}
