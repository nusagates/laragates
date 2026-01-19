<?php

namespace App\Http\Controllers;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Services\BroadcastFonnteService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BroadcastReportController extends Controller
{
    /**
     * GET /broadcast/report
     * List campaigns with summary (pagination)
     */
    public function index(Request $request)
    {
        $query = BroadcastCampaign::query()->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // using scopeForReport if exists
        $campaigns = $query->with('template')->withCount('targets')->paginate(15)->withQueryString();

        return Inertia::render('Broadcast/Report/Index', [
            'campaigns' => $campaigns,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    /**
     * GET /broadcast/report/{campaign}
     * Show campaign detail and paginated targets
     */
    public function show(Request $request, BroadcastCampaign $campaign)
    {
        $targetsQuery = BroadcastTarget::where('broadcast_campaign_id', $campaign->id)
            ->orderBy('id', 'asc');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $targetsQuery->where(function ($q2) use ($q) {
                $q2->where('phone', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%')
                    ->orWhere('error_message', 'like', '%'.$q.'%');
            });
        }

        if ($request->filled('status')) {
            $targetsQuery->where('status', $request->input('status'));
        }

        $targets = $targetsQuery->paginate(50)->withQueryString();

        return Inertia::render('Broadcast/Report/Show', [
            'campaign' => $campaign->load('template'),
            'targets' => $targets,
            'filters' => $request->only('q', 'status'),
        ]);
    }

    public function analytics()
    {
        $daily = BroadcastCampaign::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_campaigns,
            SUM(sent_count) as total_sent,
            SUM(failed_count) as total_failed
        ')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $summary = [
            'total_sent' => BroadcastCampaign::sum('sent_count'),
            'total_failed' => BroadcastCampaign::sum('failed_count'),
            'total_target' => BroadcastCampaign::sum('targets_count'),
            'success_rate' => BroadcastCampaign::sum('sent_count') > 0
                ? round((BroadcastCampaign::sum('sent_count') /
                        BroadcastCampaign::sum('targets_count')) * 100, 2)
                : 0,
        ];

        return Inertia::render('Broadcast/Analytics', [
            'daily' => $daily,
            'summary' => $summary,
        ]);
    }

    /**
     * Retry failed target
     */
    public function retryTarget(BroadcastTarget $target)
    {
        if ($target->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot retry already sent target',
            ], 400);
        }

        // Reset target status
        $target->update([
            'status' => 'pending',
            'error_message' => null,
            'attempts' => ($target->attempts ?? 0) + 1,
        ]);

        try {
            // Kirim pesan lewat Fonnte Service
            $fonnte = app(BroadcastFonnteService::class);
            $result = $fonnte->sendBroadcastMessage($target);

            // Update target status jika berhasil
            $target->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            // Update campaign counters
            $target->campaign->increment('sent_count');

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $result,
            ]);

        } catch (\Throwable $e) {
            // Update target status jika gagal
            $target->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            // Update campaign counters
            $target->campaign->increment('failed_count');

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: '.$e->getMessage(),
            ], 500);
        }
    }
}
