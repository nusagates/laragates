<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiMetricsService;

class AiMetricsController extends Controller
{
    public function index(AiMetricsService $service)
    {
        return response()->json([
            'summary_today' => $service->summaryToday(),
            'daily_trend'   => $service->dailyTrend(7),
            'top_actions'   => $service->topActions(),
            'top_users'     => $service->topUsers(),
        ]);
    }
}
