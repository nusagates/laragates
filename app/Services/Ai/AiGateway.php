<?php

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use App\Support\AiSecurity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiGateway
{
    public function call(
    string $action,
    string $prompt,
    callable $executor,
    ?int $chatSessionId = null,
    string $model = 'dummy-ai-v1',
    ?callable $fallback = null
) {
    $governance = app(AiGovernance::class);

    if (!$governance->canUseAi()) {
        return 'Fitur AI tidak tersedia untuk akun Anda.';
    }
        $startTime = microtime(true);

        $safePrompt = AiSecurity::sanitizePrompt($prompt);
        $circuit = app(AiCircuitBreaker::class);

        // ===============================
        // CIRCUIT OPEN → SKIP AI
        // ===============================
        if ($circuit->isOpen()) {
            Log::warning('[AI_GATEWAY] Circuit OPEN, fallback used');

            return $fallback
                ? $fallback()
                : 'Layanan AI sementara tidak tersedia.';
        }

        try {
            $response = $executor($safePrompt);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            AiRequestLog::create([
                'user_id'         => Auth::id(),
                'chat_session_id' => $chatSessionId,
                'action'          => $action,
                'model'           => $model,
                'prompt_hash'     => AiSecurity::hashPrompt($safePrompt),
                'response_status' => 'success',
                'latency_ms'      => $latencyMs,
            ]);

            // ===============================
            // RESET CIRCUIT
            // ===============================
            $circuit->recordSuccess();

            return $response;

        } catch (Throwable $e) {

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            AiRequestLog::create([
                'user_id'         => Auth::id(),
                'chat_session_id' => $chatSessionId,
                'action'          => $action,
                'model'           => $model,
                'prompt_hash'     => AiSecurity::hashPrompt($safePrompt),
                'response_status' => 'failed',
                'latency_ms'      => $latencyMs,
                'error_message'   => $e->getMessage(),
            ]);

            $circuit->recordFailure();

            Log::error('[AI_GATEWAY] AI failed', [
                'error' => $e->getMessage(),
            ]);

            return $fallback
                ? $fallback()
                : 'Terjadi kendala pada layanan AI.';
        }
    }
}
