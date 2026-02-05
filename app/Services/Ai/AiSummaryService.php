<?php

namespace App\Services\Ai;

use App\Models\ChatMessage;
use Illuminate\Support\Str;

class AiSummaryService
{
    /**
     * Generate summary untuk 1 chat session
     */
    public function generate(int $chatSessionId): string
    {
        // ===============================
        // AMBIL KONTEN CHAT TERAKHIR
        // ===============================
        $messages = ChatMessage::where('chat_session_id', $chatSessionId)
            ->orderBy('created_at', 'desc')
            ->limit(40) // enterprise-safe: batasi konteks
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return 'Tidak ada percakapan untuk diringkas.';
        }

        // ===============================
        // FORMAT KE PROMPT
        // ===============================
        $conversation = $messages->map(function ($msg) {
            $role = $msg->is_outgoing ? 'Agent' : 'Customer';
            $text = $msg->message ?: '[media]';

            return "{$role}: {$text}";
        })->implode("\n");

        $prompt = <<<PROMPT
Ringkas percakapan berikut secara singkat, jelas, dan profesional.
Fokus pada inti permasalahan dan hasil akhir.

Percakapan:
{$conversation}
PROMPT;

        // ===============================
        // PANGGIL AI VIA GATEWAY
        // ===============================
        $gateway = app(AiGateway::class);

        return $gateway->call(
    action: 'chat_summary',
    prompt: $prompt,
    executor: function (string $safePrompt) {
        return 'Ringkasan AI: ' .
            \Illuminate\Support\Str::limit($safePrompt, 200);
    },
    chatSessionId: $chatSessionId,
    model: 'dummy-ai-summary-v1',
    fallback: function () {
        return 'Ringkasan tidak tersedia. Silakan ditangani oleh agent.';
    }
);


    }
}
