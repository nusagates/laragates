<?php

namespace App\Services\Ai;

use App\Models\AiRequestLog;
use Illuminate\Support\Facades\DB;

class AiMetricsService
{
    /**
     * Ringkasan global (hari ini)
     */
    public function summaryToday(): array
    {
        $today = now()->toDateString();

        $base = AiRequestLog::whereDate('created_at', $today);

        return [
            'total_requests' => (clone $base)->count(),
            'success'        => (clone $base)->where('response_status', 'success')->count(),
            'failed'         => (clone $base)->where('response_status', 'failed')->count(),
            'avg_latency_ms' => (int) (clone $base)->avg('latency_ms'),
        ];
    }

    /**
     * Tren harian (last N days)
     */
    public function dailyTrend(int $days = 7)
    {
        return AiRequestLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(response_status = 'success') as success"),
                DB::raw("SUM(response_status = 'failed') as failed"),
                DB::raw('AVG(latency_ms) as avg_latency')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Top AI action
     */
    public function topActions(int $limit = 5)
    {
        return AiRequestLog::select(
                'action',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Top users
     */
    public function topUsers(int $limit = 5)
    {
        return AiRequestLog::select(
                'user_id',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }
}
