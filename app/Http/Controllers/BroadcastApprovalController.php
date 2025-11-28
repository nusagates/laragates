<?php

namespace App\Http\Controllers;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastApproval;
use App\Jobs\ProcessBroadcastJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BroadcastApprovalController extends Controller
{
    /**
     * Agent Submit Approval
     * Status: draft → pending_approval
     */
    public function requestApproval(Request $request, BroadcastCampaign $campaign)
    {
        if ($campaign->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Campaign must be in DRAFT to request approval.'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $campaign) {

            $campaign->status = 'pending_approval';
            $campaign->save();

            BroadcastApproval::create([
                'broadcast_campaign_id' => $campaign->id,
                'requested_by'          => auth()->id(),
                'request_notes'         => $request->notes,
                'action'                => 'requested',
                'acted_by'              => null,
                'action_notes'          => null,
                'acted_at'              => now(),
                'snapshot'              => $campaign->toArray(),
            ]);
        });

        return response()->json([
            'success' => true,
            'status'  => 'pending_approval'
        ]);
    }

    /**
     * Admin Approves Campaign
     * Status: pending_approval → approved → running/scheduled
     */
    public function approve(Request $request, BroadcastCampaign $campaign)
    {
        if ($campaign->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not in pending approval state.'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $campaign) {

            $campaign->status      = 'approved';
            $campaign->approved_by = auth()->id();
            $campaign->approved_at = now();
            $campaign->save();

            BroadcastApproval::create([
                'broadcast_campaign_id' => $campaign->id,
                'requested_by'          => $campaign->created_by,
                'action'                => 'approved',
                'acted_by'              => auth()->id(),
                'action_notes'          => $request->notes,
                'acted_at'              => now(),
                'snapshot'              => $campaign->toArray(),
            ]);

            // RUN BROADCAST JOB
            if ($campaign->send_now) {
                dispatch(new ProcessBroadcastJob($campaign));
                $campaign->status = 'running';
            } else if ($campaign->send_at) {
                $delaySeconds = max(0, Carbon::parse($campaign->send_at)->diffInSeconds());
                dispatch((new ProcessBroadcastJob($campaign))->delay($delaySeconds));
                $campaign->status = 'scheduled';
            }

            $campaign->save();
        });

        return response()->json([
            'success' => true,
            'status'  => $campaign->status,
            'message' => 'Campaign approved.'
        ]);
    }

    /**
     * Admin Rejects Campaign
     * Status: pending_approval → rejected
     */
    public function reject(Request $request, BroadcastCampaign $campaign)
    {
        if ($campaign->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not pending approval.'
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($request, $campaign) {

            $campaign->status = 'rejected';
            $campaign->save();

            BroadcastApproval::create([
                'broadcast_campaign_id' => $campaign->id,
                'requested_by'          => $campaign->created_by,
                'action'                => 'rejected',
                'acted_by'              => auth()->id(),
                'action_notes'          => $request->notes,
                'acted_at'              => now(),
                'snapshot'              => $campaign->toArray(),
            ]);
        });

        return response()->json([
            'success' => true,
            'status'  => 'rejected',
        ]);
    }
}
