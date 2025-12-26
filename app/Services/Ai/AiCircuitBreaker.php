<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;

class AiCircuitBreaker
{
    protected string $key = 'ai:circuit';

    public function isOpen(): bool
    {
        return Cache::get($this->key . ':open', false);
    }

    public function recordFailure(): void
    {
        $failures = Cache::increment($this->key . ':failures');

        Cache::put($this->key . ':failures', $failures, now()->addMinutes(5));

        if ($failures >= config('ai.circuit_breaker.failure_threshold')) {
            Cache::put(
                $this->key . ':open',
                true,
                now()->addSeconds(config('ai.circuit_breaker.cooldown_seconds'))
            );
        }
    }

    public function recordSuccess(): void
    {
        Cache::forget($this->key . ':failures');
        Cache::forget($this->key . ':open');
    }
}
