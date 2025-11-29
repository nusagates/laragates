<?php

namespace App\Http\Controllers;

use App\Models\BroadcastApproval;
use App\Models\BroadcastCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BroadcastApprovalController extends Controller
{
    /**
     * Agent: request approval for a campaign
     * POST /broadcast/{campaign}/request-approval
     */
    public function requestApproval(Request $request, BroadcastCampaign $campaign)
    {
        // Allow if creator or agent (adjust this to your permission system)
        if ($campaign->created_by && $campaign->created_by !== Auth::id() && !Auth::user()->hasRole('agent')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'notes' => 'nullable|string',
        ]);

        // Create approval entry with campaign snapshot
        $snapshot = $campaign->toArray();

        $approval = BroadcastApproval::create([
            'broadcast_campaign_id' => $campaign->id,
            'requested_by' => Auth::id(),
            'request_notes' => $data['notes'] ?? null,
            'action' => 'requested',
            'snapshot' => $snapshot,
        ]);

        // Set campaign status to pending_approval
        $campaign->markPendingApproval();

        return response()->json(['ok' => true, 'approval' => $approval]);
    }

    /**
     * Admin: list approvals (Inertia)
     * GET /broadcast/approvals
     */
    public function index(Request $request)
    {
        $query = BroadcastApproval::with(['campaign.template', 'requester'])
            ->orderBy('created_at', 'desc');

        // optional filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $approvals = $query->paginate(20)->withQueryString();

        return Inertia::render('Broadcast/Approvals/Index', [
            'approvals' => $approvals,
            'filters' => $request->only('action'),
        ]);
    }

    /**
     * Admin: approve
     * POST /broadcast/approvals/{approval}/approve
     */
    public function approve(Request $request, BroadcastApproval $approval)
    {
        $this->authorizeAdmin();

        if ($approval->action === 'approved') {
            return response()->json(['error' => 'Already approved'], 400);
        }

        DB::transaction(function () use ($approval, $request) {
            $notes = $request->input('note') ?? null;

            // mark approval
            $approval->update([
                'action' => 'approved',
                'acted_by' => Auth::id(),
                'action_notes' => $notes,
                'acted_at' => now(),
            ]);

            // update campaign status
            $campaign = $approval->campaign;
            $campaign->markApproved(Auth::id());

            // if send_now true -> optionally dispatch sending job
            if ($campaign->send_now) {
                if (class_exists(\App\Jobs\ProcessBroadcastJob::class)) {
                    \App\Jobs\ProcessBroadcastJob::dispatch($campaign->id);
                } else {
                    // fallback: if you use a different job (e.g. SendScheduledBroadcastJob),
                    // you can dispatch it here or call existing send logic.
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Admin: reject
     * POST /broadcast/approvals/{approval}/reject
     */
    public function reject(Request $request, BroadcastApproval $approval)
    {
        $this->authorizeAdmin();

        if ($approval->action === 'rejected') {
            return response()->json(['error' => 'Already rejected'], 400);
        }

        $notes = $request->input('note') ?? null;

        DB::transaction(function () use ($approval, $notes) {
            $approval->update([
                'action' => 'rejected',
                'acted_by' => Auth::id(),
                'action_notes' => $notes,
                'acted_at' => now(),
            ]);

            $campaign = $approval->campaign;
            $campaign->markRejected(Auth::id(), $notes);
        });

        return response()->json(['ok' => true]);
    }

    protected function authorizeAdmin()
    {
        // adapt this to your roles/permissions implementation
        if (!Auth::user()->hasRole('superadmin') && !Auth::user()->hasRole('admin')) {
            abort(403);
        }
    }
}
