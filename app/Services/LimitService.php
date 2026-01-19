<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\UsageCounter;
use App\Exceptions\QuotaExceededException;
use Carbon\Carbon;

class LimitService
{
    public static function check(string $key, int $amount = 1): void
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return;
        }

        $subscription = Subscription::with('plan')
            ->where('company_id', $user->company_id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            throw new QuotaExceededException('Subscription inactive');
        }

        if (now()->gt($subscription->end_at)) {
            throw new QuotaExceededException('Subscription expired');
        }

        $limit = $subscription->plan->limits[$key] ?? null;
        if ($limit === null) {
            return; // unlimited
        }

        $counter = self::counter($user->company_id, $key);

        if (($counter->used + $amount) > $limit) {
            throw new QuotaExceededException("Monthly quota exceeded: {$key}");
        }
    }

    public static function consume(string $key, int $amount = 1): void
    {
        $user = auth()->user();
        if (!$user || !$user->company_id) {
            return;
        }

        self::counter($user->company_id, $key)
            ->increment('used', $amount);
    }

    protected static function counter(int $companyId, string $key): UsageCounter
    {
        $now = Carbon::now();

        $counter = UsageCounter::firstOrCreate(
            [
                'company_id' => $companyId,
                'key' => $key
            ],
            [
                'used' => 0,
                'period_start' => $now->copy()->startOfMonth(),
                'period_end' => $now->copy()->endOfMonth(),
            ]
        );

        if ($now->gt($counter->period_end)) {
            $counter->update([
                'used' => 0,
                'period_start' => $now->copy()->startOfMonth(),
                'period_end' => $now->copy()->endOfMonth(),
            ]);
        }

        return $counter;
    }
}
