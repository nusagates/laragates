<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiSummaryService;
use Illuminate\Http\Request;

class AiSummaryController extends Controller
{
    public function generate(Request $request, AiSummaryService $service)
    {
        $request->validate([
            'chat_session_id' => 'required|integer|exists:chat_sessions,id',
        ]);

        $this->authorize('use-ai-summary');

        $summary = $service->generate(
            $request->chat_session_id
        );

        return response()->json([
            'id' => $summary->id,
            'summary' => $summary->summary_text,
            'created_at' => $summary->created_at,
        ]);
    }
}
