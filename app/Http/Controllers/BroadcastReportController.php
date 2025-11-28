<?php

namespace App\Http\Controllers;

use App\Models\BroadcastCampaign;
use Illuminate\Http\Request;

class BroadcastReportController extends Controller
{
    /**
     * GET /broadcast/report
     * Query params:
     *  - q (search campaign name)
     *  - status (draft|pending_approval|approved|scheduled|running|done|failed)
     *  - date_from, date_to (YYYY-MM-DD)
     *  - page (pagination)
     */
    public function index(Request $request)
    {
        $query = BroadcastCampaign::query()->withCount('targets');

        // filters
        if ($q = $request->query('q')) {
            $query->where('name', 'like', "%{$q}%");
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // order newest first
        $perPage = (int) $request->query('per_page', 15);

        $paged = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // map minimal useful fields
        $data = $paged->through(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'template' => optional($c->template)->name,
                'audience_type' => $c->audience_type,
                'total_targets' => $c->total_targets,
                'targets_count' => $c->targets_count,
                'sent_count' => (int) $c->sent_count,
                'failed_count' => (int) $c->failed_count,
                'status' => $c->status,
                'send_now' => (bool) $c->send_now,
                'send_at' => $c->send_at,
                'created_at' => $c->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'meta' => [
                'total' => $paged->total(),
                'per_page' => $paged->perPage(),
                'current_page' => $paged->currentPage(),
                'last_page' => $paged->lastPage(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * GET /broadcast/report/{campaign}
     * Return detail: campaign summary + paginated targets
     * Query params for targets: page, per_page, status (pending/success/failed)
     */
    public function show(Request $request, BroadcastCampaign $campaign)
    {
        // campaign summary
        $summary = [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'template' => optional($campaign->template)->name,
            'audience_type' => $campaign->audience_type,
            'total_targets' => $campaign->total_targets,
            'sent_count' => (int) $campaign->sent_count,
            'failed_count' => (int) $campaign->failed_count,
            'status' => $campaign->status,
            'send_now' => (bool) $campaign->send_now,
            'send_at' => $campaign->send_at,
            'created_at' => $campaign->created_at,
        ];

        // targets pagination and filter
        $targetsQuery = $campaign->targets()->orderBy('created_at', 'asc');

        if ($status = $request->query('status')) {
            $targetsQuery->where('status', $status);
        }

        $perPage = (int) $request->query('per_page', 25);
        $targetsPage = $targetsQuery->paginate($perPage);

        // map target fields
        $targets = $targetsPage->through(function ($t) {
            return [
                'id' => $t->id,
                'phone' => $t->phone,
                'variables' => $t->variables,
                'status' => $t->status,
                'wa_message_id' => $t->wa_message_id,
                'error_message' => $t->error_message,
                'sent_at' => $t->sent_at,
                'created_at' => $t->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'campaign' => $summary,
            'targets' => $targets,
            'targets_meta' => [
                'total' => $targetsPage->total(),
                'per_page' => $targetsPage->perPage(),
                'current_page' => $targetsPage->currentPage(),
                'last_page' => $targetsPage->lastPage(),
            ],
        ]);
    }
}
