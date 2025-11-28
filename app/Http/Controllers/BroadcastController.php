<?php

namespace App\Http\Controllers;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class BroadcastController extends Controller
{
    /**
     * MAIN PAGE (Blade / Inertia)
     */
    public function index()
    {
        return inertia('Broadcast/Index', [
            'campaigns' => BroadcastCampaign::with('template')
                ->latest()
                ->paginate(20)
        ]);
    }

    /**
     * CREATE CAMPAIGN (DRAFT)
     * POST /broadcast/campaigns
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'whatsapp_template_id'  => 'required|exists:whatsapp_templates,id',
            'audience_type'         => 'required|in:all,upload',
            'send_now'              => 'required|boolean',
            'send_at'               => 'nullable|date',
        ]);

        $template = WhatsappTemplate::findOrFail($request->whatsapp_template_id);

        return DB::transaction(function () use ($request, $template) {

            $campaign = BroadcastCampaign::create([
                'name'                  => $request->name,
                'whatsapp_template_id'  => $template->id,
                'audience_type'         => $request->audience_type,
                'send_now'              => $request->send_now,
                'send_at'               => $request->send_at ? Carbon::parse($request->send_at) : null,
                'status'                => 'draft',
                'created_by'            => auth()->id(),
                'total_targets'         => 0,
            ]);

            return response()->json([
                'success'   => true,
                'campaign'  => $campaign,
                'message'   => 'Campaign created as draft.'
            ]);
        });
    }

    /**
     * UPLOAD RECIPIENT LIST (CSV / XLSX)
     * POST /broadcast/campaigns/{campaign}/upload-targets
     */
    public function uploadTargets(Request $request, BroadcastCampaign $campaign)
    {
        if ($campaign->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Targets can only be uploaded while campaign is in DRAFT status.'
            ], 400);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx'
        ]);

        $rows = $this->parseFile($request->file('file'));

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file is empty.'
            ], 422);
        }

        // Validate and Insert
        $template = $campaign->template;
        $requiredVars = $template->body_params_count ?? $this->detectVariables($template->body);

        $insertData = [];
        $count = 0;

        foreach ($rows as $row) {

            $name   = $row['name']  ?? $row[0] ?? null;
            $phone  = $row['phone'] ?? $row[1] ?? null;

            $phone = $this->normalizePhone($phone);

            if (!$phone) continue;

            // Extract variables if available
            $variables = $row['variables'] ?? [];
            if (!is_array($variables)) {
                $variables = [];
            }

            // Validate variables count
            if ($requiredVars > 0 && count($variables) < $requiredVars) {
                return response()->json([
                    'success' => false,
                    'message' => "Row has invalid variable count for template. Required: {$requiredVars}"
                ], 422);
            }

            $insertData[] = [
                'broadcast_campaign_id' => $campaign->id,
                'name'      => $name,
                'phone'     => $phone,
                'variables' => json_encode($variables),
                'status'    => 'pending',
                'created_at'=> now(),
                'updated_at'=> now(),
            ];

            $count++;
        }

        if ($count > 0) {
            BroadcastTarget::insert($insertData);
            $campaign->total_targets = $campaign->total_targets + $count;
            $campaign->save();
        }

        return response()->json([
            'success' => true,
            'added'   => $count,
            'message' => "Successfully added {$count} recipients."
        ]);
    }

    /**
     * Helper: Detect variables in template body ({{1}}, {{2}})
     */
    private function detectVariables($text)
    {
        preg_match_all('/\{\{\d+\}\}/', $text, $matches);
        return count($matches[0]);
    }

    /**
     * Helper: Parse CSV / XLSX
     */
    private function parseFile($file)
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $rows = [];

        if ($ext === 'xlsx') {
            $data = Excel::toArray([], $file);
            $sheet = $data[0];

            // skip header automatically
            foreach ($sheet as $i => $row) {
                if ($i === 0) continue; 
                $rows[] = $row;
            }
        } else {
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                $i = 0;
                while (($data = fgetcsv($handle, 0, ",")) !== false) {
                    if ($i === 0) { $i++; continue; } // skip header
                    $rows[] = $data;
                }
                fclose($handle);
            }
        }

        return $rows;
    }

    /**
     * Normalize phone number to WhatsApp format
     * Ex: 08123 → 628123
     */
    private function normalizePhone($phone)
    {
        if (!$phone) return null;

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, "0")) {
            return "62" . substr($phone, 1);
        }

        if (str_starts_with($phone, "62")) {
            return $phone;
        }

        if (strlen($phone) > 6) {
            return "62" . $phone;
        }

        return null;
    }
}
