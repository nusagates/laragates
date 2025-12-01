<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * ==========================
     * TOP CARDS (METRICS)
     * ==========================
     */
    public function metrics()
    {
        $today = Carbon::today();

        return response()->json([
            'messages_today' => DB::table('chat_messages')
                ->whereDate('created_at', $today)
                ->count(),

            'active_sessions' => DB::table('chat_sessions')
                ->where('status', 'open')
                ->count(),

            'avg_response_time' => '0s', // akan diganti di response-time API
            'best_agent' => $this->bestAgent(),
        ]);
    }

    private function bestAgent()
    {
        $best = DB::table('chat_messages')
            ->join('users', 'users.id', '=', 'chat_messages.user_id')
            ->select('users.name', DB::raw('COUNT(chat_messages.id) as handled'))
            ->where('is_outgoing', 1)
            ->groupBy('chat_messages.user_id', 'users.name')
            ->orderByDesc('handled')
            ->first();

        return $best->name ?? "-";
    }

    /**
     * ==========================
     * 7-DAY TREND
     * ==========================
     */
    public function trends()
    {
        $rows = DB::table('chat_messages')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(is_outgoing = 0) as inbound'),
                DB::raw('SUM(is_outgoing = 1) as outbound')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(7)
            ->get();

        return response()->json([
            'labels' => $rows->pluck('date'),
            'inbound' => $rows->pluck('inbound'),
            'outbound' => $rows->pluck('outbound'),
        ]);
    }

    /**
     * ==========================
     * AGENT PERFORMANCE
     * ==========================
     */
    public function agents()
    {
        $rows = DB::table('chat_messages')
            ->join('users', 'users.id', '=', 'chat_messages.user_id')
            ->select('users.name', DB::raw('COUNT(chat_messages.id) as handled'))
            ->where('is_outgoing', 1)
            ->groupBy('chat_messages.user_id', 'users.name')
            ->orderByDesc('handled')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('name'),
            'counts' => $rows->pluck('handled'),
        ]);
    }

    /**
     * ==========================
     * RESPONSE TIME (PEKAN 1)
     * ==========================
     */
    public function responseTime(Request $req)
    {
        $days = (int)($req->input('days', 7));

        $start = Carbon::now()->subDays($days)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $rows = DB::table('chat_messages as m1')
            ->where('m1.is_outgoing', 0)
            ->whereBetween('m1.created_at', [$start, $end])
            ->selectRaw("
                m1.id,
                m1.chat_session_id,
                m1.created_at as inbound_at,
                (
                    SELECT MIN(m2.created_at)
                    FROM chat_messages m2
                    WHERE m2.chat_session_id = m1.chat_session_id
                      AND m2.is_outgoing = 1
                      AND m2.created_at > m1.created_at
                ) as first_outbound_at
            ")
            ->havingRaw("first_outbound_at IS NOT NULL")
            ->get();

        // compute diffs in seconds
        $diffs = $rows->map(fn($r) =>
            strtotime($r->first_outbound_at) - strtotime($r->inbound_at)
        )->filter(fn($x) => $x >= 0)->values();

        $count = $diffs->count();
        $avgSeconds = $count ? (int)round($diffs->sum() / $count) : 0;

        return response()->json([
            'count_pairs' => $count,
            'avg_seconds' => $avgSeconds,
            'avg_human' => $this->formatSeconds($avgSeconds),
        ]);
    }

    private function formatSeconds($s)
    {
        if ($s <= 0) return "0s";
        $h = floor($s / 3600);
        $m = floor(($s % 3600) / 60);
        $sec = $s % 60;

        if ($h) return "{$h}h {$m}m {$sec}s";
        if ($m) return "{$m}m {$sec}s";
        return "{$sec}s";
    }


    /**
     * ==========================
     * PEAK HOURS
     * ==========================
     */
    public function peakHours()
{
    $rows = DB::table('chat_messages')
        ->selectRaw("HOUR(created_at) as hour, COUNT(*) as total")
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

    // Normalisasi: pastikan semua jam 0-23 ada
    $final = [];
    for ($i = 0; $i < 24; $i++) {
        $match = $rows->firstWhere('hour', $i);
        $final[] = [
            'hour' => sprintf('%02d:00', $i),
            'total' => $match->total ?? 0
        ];
    }

    return response()->json($final);
}

    /**
     * ==========================
     * ACTIVE SESSIONS
     * ==========================
     */
    public function sessions()
    {
        return DB::table('chat_sessions')
            ->where('status', 'open')
            ->get();
    }
}
