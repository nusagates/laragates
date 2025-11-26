<?php

namespace App\Jobs;

use App\Events\Chat\MessageStatusUpdated;
use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    public function handle(): void
    {
        // TODO: isi logic call ke provider WABA kamu
        // Contoh pseudo:

        try {
            // $response = Http::post('https://provider-waba/send', [...]);

            // Misal sukses:
            $this->message->update([
                'status'        => 'sent',
                // 'wa_message_id' => $response['id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->message->update(['status' => 'failed']);
        }

        broadcast(new MessageStatusUpdated($this->message->fresh()))->toOthers();
    }
}
