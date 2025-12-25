<?php

namespace App\Services\Ai;

use App\Models\ChatMessage;
use App\Models\ChatSummary;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;

class AiSummaryService
{
    public function generate(int $chatSessionId): ChatSummary
    {
        // 1️⃣ Ambil pesan (READ ONLY)
        $messages = ChatMessage::where('chat_session_id', $chatSessionId)
            ->orderBy('created_at', 'desc')
            ->limit(40)
            ->get()
            ->reverse()
            ->map(function ($m) {
                return [
                    'role' => $m->is_outgoing ? 'agent' : 'customer',
                    'content' => $m->message ?? '[media]',
                ];
            })
            ->values()
            ->toArray();

        // 2️⃣ Generate summary (dummy)
        $summaryText = app(DummySummaryService::class)
            ->summarize($messages);

        // 3️⃣ Simpan summary
        $summary = ChatSummary::create([
            'chat_session_id' => $chatSessionId,
            'summary_text' => $summaryText,
            'created_by' => Auth::id(),
        ]);

        // 4️⃣ SYSLOG (AUDIT EVENT)
        SystemLog::create([
            'event' => 'ai_summary_generated',
            'user_id' => Auth::id(),
            'entity_type' => 'chat_summary',
            'entity_id' => $summary->id,
            'meta' => [
                'chat_session_id' => $chatSessionId,
                'mode' => 'dummy',
                'message_count' => count($messages),
            ],
        ]);

        // 5️⃣ Return hasil
        return $summary;
    }
}
