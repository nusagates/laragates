<?php

namespace App\Http\Controllers;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Models\WhatsappTemplate;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class BroadcastController extends Controller
{
    /**
     * ===============================
     * MAIN PAGE (Broadcast Create)
     * AUDIT: access
     * ===============================
     */
    public function index()
    {
        $templates = WhatsappTemplate::where('status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get();

        $history = BroadcastCampaign::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'id'     => $c->id,
                    'name'   => $c->name,
                    'sent'   => $c->sent_count ?? 0,
                    'failed' => $c->failed_count ?? 0,
                    'date'   => $c->created_at->format('Y-m-d H:i'),
                ];
            });

        SystemLogService::record(
            'broadcast_view',
            null,
            null,
            null,
            null,
            [
                'description' => 'Opened broadcast create page',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'broadcast_module',
                    'mode'       => 'manual',
                ],
                'risk' => [
                    'level' => 'low',
                ],
            ]
        );

        return Inertia::render('Broadcast/Index', [
            'templates' => $templates,
            'history'   => $history,
        ]);
    }

    /**
     * ===============================
     * CREATE CAMPAIGN (DRAFT)
     * AUDIT: data creation
     * ===============================
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'template_id'   => 'required|exists:whatsapp_templates,id',
            'audience_type' => 'required|in:all,csv',
            'schedule_type' => 'required|in:now,later',
            'send_at'       => 'nullable|date',
        ]);

        $template = WhatsappTemplate::findOrFail($request->template_id);

        return DB::transaction(function () use ($request, $template) {

            $campaign = BroadcastCampaign::create([
                'name'                 => $request->name,
                'whatsapp_template_id' => $template->id,
                'audience_type'        => $request->audience_type,
                'send_now'             => $request->schedule_type === 'now',
                'send_at'              => $request->schedule_type === 'later'
                                            ? Carbon::parse($request->send_at)
                                            : null,
                'status'               => 'draft',
                'created_by'           => auth()->id(),
                'total_targets'        => 0,
            ]);

            SystemLogService::record(
                'broadcast_create',
                'broadcast_campaign',
                $campaign->id,
                null,
                $campaign->toArray(),
                [
                    'description' => 'Broadcast campaign "' . $campaign->name . '" created',
                    'audit' => [
                        'actor_id'   => auth()->id(),
                        'actor_role' => auth()->user()->role ?? null,
                        'source'     => 'broadcast_module',
                        'mode'       => 'manual',
                    ],
                    'broadcast' => [
                        'id'     => $campaign->id,
                        'name'   => $campaign->name,
                        'status' => $campaign->status,
                    ],
                    'impact' => [
                        'total_targets' => 0,
                    ],
                    'risk' => [
                        'level' => 'low',
                    ],
                ]
            );

            return response()->json([
                'success'  => true,
                'campaign' => $campaign,
            ]);
        });
    }

    /**
     * ===============================
     * UPLOAD TARGETS (CSV / XLSX)
     * AUDIT: data import + anomaly detection
     * ===============================
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

        $template = $campaign->template;
        $requiredVars = $template->body_params_count ?? $this->detectVariables($template->body);

        $insertData = [];
        $count = 0;

        foreach ($rows as $row) {

            $name  = $row['name']  ?? $row[0] ?? null;
            $phone = $row['phone'] ?? $row[1] ?? null;

            $phone = $this->normalizePhone($phone);
            if (!$phone) continue;

            $variables = $row['variables'] ?? [];
            if (!is_array($variables)) {
                $variables = [];
            }

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
            $campaign->total_targets += $count;
            $campaign->save();
        }

        $riskLevel = $count > 10000 ? 'high' : ($count > 3000 ? 'medium' : 'low');

        SystemLogService::record(
            'broadcast_target_upload',
            'broadcast_campaign',
            $campaign->id,
            null,
            null,
            [
                'description' => "Uploaded {$count} broadcast targets",
                'audit' => [
                    'actor_id' => auth()->id(),
                    'source'   => 'broadcast_module',
                    'mode'     => 'manual',
                ],
                'impact' => [
                    'added'         => $count,
                    'total_targets' => $campaign->total_targets,
                ],
                'risk' => [
                    'level'  => $riskLevel,
                    'reason' => $riskLevel !== 'low' ? 'Large target upload' : null,
                ],
            ]
        );

        return response()->json([
            'success' => true,
            'added'   => $count,
            'message' => "Successfully added {$count} recipients."
        ]);
    }

    /**
     * ===============================
     * REPORT PAGE
     * AUDIT: access
     * ===============================
     */
    public function report(Request $request)
    {
        $query = BroadcastCampaign::with('template', 'creator')
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        SystemLogService::record(
            'broadcast_report_view',
            null,
            null,
            null,
            null,
            [
                'description' => 'Viewed broadcast report',
                'audit' => [
                    'actor_id'   => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source'     => 'broadcast_module',
                ],
                'filters' => $request->only('search', 'status'),
                'risk' => [
                    'level' => 'low',
                ],
            ]
        );

        return Inertia::render('Broadcast/Report', [
            'campaigns' => $query->paginate(10)->withQueryString(),
            'filters'   => $request->only('search', 'status'),
        ]);
    }

    /* =====================================================
     | HELPERS (NO BUSINESS CHANGE)
     =====================================================*/

    private function detectVariables($text)
    {
        preg_match_all('/\{\{\d+\}\}/', $text, $matches);
        return count($matches[0]);
    }

    private function parseFile($file)
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $rows = [];

        if ($ext === 'xlsx') {
            $data = Excel::toArray([], $file)[0];
            foreach ($data as $i => $row) {
                if ($i === 0) continue;
                $rows[] = $row;
            }
        } else {
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                $i = 0;
                while (($data = fgetcsv($handle, 0, ",")) !== false) {
                    if ($i === 0) { $i++; continue; }
                    $rows[] = $data;
                }
                fclose($handle);
            }
        }

        return $rows;
    }

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
