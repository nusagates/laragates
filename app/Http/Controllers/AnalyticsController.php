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

            // ✅ FIXED: avg response time sekarang real
            'avg_response_time' => $this->avgResponseTimeHuman(),

            'best_agent' => $this->bestAgent(),
        ]);
    }

    /**
     * ==========================
     * BEST AGENT
     * ==========================
     */
    private function bestAgent()
    {
        $best = DB::table('chat_messages')
            ->join('users', 'users.id', '=', 'chat_messages.user_id')
            ->select(
                'users.name',
                DB::raw('COUNT(chat_messages.id) as handled')
            )
            ->where('is_outgoing', 1)
            ->groupBy('chat_messages.user_id', 'users.name')
            ->orderByDesc('handled')
            ->first();

        return $best->name ?? "-";
    }

    /**
     * ==========================
     * 7-DAY MESSAGE TREND
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
            ->orderBy('date', 'desc')
            ->limit(7)
            ->get()
            ->reverse();

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
            ->select(
                'users.name',
                DB::raw('COUNT(chat_messages.id) as handled')
            )
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
     * RESPONSE TIME (RAW API)
     * ==========================
     */
    public function responseTime(Request $req)
    {
        $days = (int)($req->input('days', 7));

        $start = Carbon::now()->subDays($days)->startOfDay();
        $end   = Carbon::now()->endOfDay();

        $rows = DB::table('chat_messages as c')
            ->where('c.sender', 'customer')
            ->whereBetween('c.created_at', [$start, $end])
            ->selectRaw("
                c.chat_session_id,
                c.created_at as inbound_at,
                (
                    SELECT MIN(a.created_at)
                    FROM chat_messages a
                    WHERE a.chat_session_id = c.chat_session_id
                      AND a.sender = 'agent'
                      AND a.created_at > c.created_at
                ) as outbound_at
            ")
            ->havingRaw("outbound_at IS NOT NULL")
            ->get();

        $diffs = $rows->map(fn ($r) =>
            strtotime($r->outbound_at) - strtotime($r->inbound_at)
        )->filter(fn ($s) => $s >= 0)->values();

        $count = $diffs->count();
        $avgSeconds = $count ? (int) round($diffs->sum() / $count) : 0;

        return response()->json([
            'count_pairs' => $count,
            'avg_seconds' => $avgSeconds,
            'avg_human' => $this->formatSeconds($avgSeconds),
        ]);
    }

    /**
     * ==========================
     * AVG RESPONSE TIME (METRICS)
     * ==========================
     */
    private function avgResponseTimeHuman()
    {
        $row = DB::selectOne("
            SELECT
              AVG(TIMESTAMPDIFF(SECOND, c.created_at, a.created_at)) AS avg_seconds
            FROM chat_messages c
            JOIN chat_messages a
              ON a.chat_session_id = c.chat_session_id
             AND a.sender = 'agent'
             AND a.created_at > c.created_at
            WHERE c.sender = 'customer'
              AND a.created_at = (
                SELECT MIN(a2.created_at)
                FROM chat_messages a2
                WHERE a2.chat_session_id = c.chat_session_id
                  AND a2.sender = 'agent'
                  AND a2.created_at > c.created_at
              )
        ");

        $seconds = (int) ($row->avg_seconds ?? 0);

        return $this->formatSeconds($seconds);
    }

    /**
     * ==========================
     * FORMAT SECONDS
     * ==========================
     */
    private function formatSeconds(int $s)
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

        $final = [];
        for ($i = 0; $i < 24; $i++) {
            $match = $rows->firstWhere('hour', $i);
            $final[] = [
                'hour' => $i,
                'total' => $match->total ?? 0,
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
            ->orderByDesc('updated_at')
            ->get();
    }
}
