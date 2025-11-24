<?php

namespace App\Http\Controllers;

use App\Jobs\SendBroadcastCampaignJob;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BroadcastController extends Controller
{
    public function index()
    {
        $templates = WhatsappTemplate::orderBy('name')->get();

        $history = BroadcastCampaign::with('template')
            ->latest()
            ->take(10)
            ->get()
            ->map(function (BroadcastCampaign $c) {
                return [
                    'id'        => $c->id,
                    'name'      => $c->name,
                    'template'  => $c->template?->name,
                    'status'    => $c->status,
                    'sent'      => $c->sent_count,
                    'failed'    => $c->failed_count,
                    'date'      => optional($c->created_at)->format('Y-m-d'),
                ];
            });

        return Inertia::render('Broadcast/Index', [
            'templates' => $templates,
            'history'   => $history,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'template_id'     => 'required|exists:whatsapp_templates,id',
            'audience_type'   => 'required|in:all,csv',
            'schedule_type'   => 'required|in:now,later',
            'send_at'         => 'nullable|date',
            'csv_file'        => 'nullable|file|mimes:csv,txt',
        ]);

        // Untuk saat ini kita implement dulu tipe CSV.
        if ($data['audience_type'] === 'csv' && ! $request->hasFile('csv_file')) {
            return back()->withErrors(['csv_file' => 'CSV file is required for CSV audience.']);
        }

        return DB::transaction(function () use ($request, $data) {

            $campaign = BroadcastCampaign::create([
                'name'                 => $data['name'],
                'whatsapp_template_id' => $data['template_id'],
                'audience_type'        => $data['audience_type'],
                'status'               => $data['schedule_type'] === 'now' ? 'pending' : 'scheduled',
                'send_now'             => $data['schedule_type'] === 'now',
                'send_at'              => $data['schedule_type'] === 'later'
                    ? Carbon::parse($data['send_at'])
                    : null,
            ]);

            $targets = [];

            if ($data['audience_type'] === 'csv') {
                $file = $request->file('csv_file');
                $handle = fopen($file->getRealPath(), 'r');

                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    if (!isset($row[0])) {
                        continue;
                    }
                    $phone = trim($row[0]);
                    if ($phone === '' || !preg_match('/^\d+$/', $phone)) {
                        continue;
                    }

                    $targets[] = [
                        'broadcast_campaign_id' => $campaign->id,
                        'phone'                 => $phone,
                        'status'                => 'pending',
                        'created_at'            => now(),
                        'updated_at'            => now(),
                    ];
                }

                fclose($handle);
            }

            // TODO: audience_type 'all' -> load semua customer dari tabel customers

            if (!empty($targets)) {
                BroadcastTarget::insert($targets);
                $campaign->update(['total_targets' => count($targets)]);
            }

            // Dispatch job
            if ($campaign->send_now) {
                SendBroadcastCampaignJob::dispatch($campaign);
            } elseif ($campaign->send_at) {
                SendBroadcastCampaignJob::dispatch($campaign)->delay($campaign->send_at);
            }

            return redirect()
                ->route('broadcast')
                ->with('success', 'Broadcast campaign created!');
        });
    }
}
