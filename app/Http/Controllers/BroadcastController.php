<?php

namespace App\Http\Controllers;

use App\Models\BroadcastApproval;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastTarget;
use App\Models\WhatsappTemplate;
use App\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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
                    'id' => $c->id,
                    'name' => $c->name,
                    'sent' => $c->sent_count ?? 0,
                    'failed' => $c->failed_count ?? 0,
                    'date' => $c->created_at->format('Y-m-d H:i'),
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
                    'actor_id' => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source' => 'broadcast_module',
                    'mode' => 'manual',
                ],
                'risk' => [
                    'level' => 'low',
                ],
            ]
        );

        return Inertia::render('Broadcast/Index', [
            'templates' => $templates,
            'history' => $history,
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
            'name' => 'required|string|max:255',
            'template_id' => 'required|exists:whatsapp_templates,id',
            'audience_type' => 'required|in:all,upload',
            'schedule_type' => 'required|in:now,later',
            'send_at' => 'nullable|date',
            'csv_file' => 'nullable|file|mimes:csv,txt',
        ]);

        $template = WhatsappTemplate::findOrFail($request->template_id);

        return DB::transaction(function () use ($request, $template) {

            $campaign = BroadcastCampaign::create([
                'name' => $request->name,
                'whatsapp_template_id' => $template->id,
                'audience_type' => $request->audience_type,
                'send_now' => $request->schedule_type === 'now',
                'send_at' => $request->schedule_type === 'later'
                                            ? Carbon::parse($request->send_at)
                                            : null,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'total_targets' => 0,
            ]);

            // Process CSV file if provided
            if ($request->hasFile('csv_file')) {
                $this->processTargetsForCampaign($campaign, $request->file('csv_file'));
            }

            // Auto-request approval
            $campaign->status = 'pending_approval';
            $campaign->save();

            BroadcastApproval::create([
                'broadcast_campaign_id' => $campaign->id,
                'requested_by' => auth()->id(),
                'request_notes' => 'Auto-submitted from campaign creation',
                'action' => 'requested',
            ]);

            SystemLogService::record(
                'broadcast_create',
                'broadcast_campaign',
                $campaign->id,
                null,
                $campaign->toArray(),
                [
                    'description' => 'Broadcast campaign "'.$campaign->name.'" created',
                    'audit' => [
                        'actor_id' => auth()->id(),
                        'actor_role' => auth()->user()->role ?? null,
                        'source' => 'broadcast_module',
                        'mode' => 'manual',
                    ],
                    'broadcast' => [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'status' => $campaign->status,
                    ],
                    'impact' => [
                        'total_targets' => $campaign->total_targets,
                    ],
                    'risk' => [
                        'level' => $campaign->total_targets > 5000 ? 'medium' : 'low',
                    ],
                ]
            );

            // Refresh campaign to get updated data
            $campaign->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Broadcast campaign created successfully',
                'campaign' => $campaign->load('template'),
            ]);
        });
    }

    /**
     * ===============================
     * PROCESS TARGETS (INTERNAL HELPER)
     * ===============================
     */
    protected function processTargetsForCampaign(BroadcastCampaign $campaign, $file): void
    {
        $rows = $this->parseFile($file);

        if (empty($rows)) {
            Log::warning('No rows parsed from CSV file', [
                'campaign_id' => $campaign->id,
                'file_name' => $file->getClientOriginalName(),
            ]);

            return;
        }

        $template = $campaign->template;
        $requiredVars = $template->body_params_count ?? $this->detectVariables($template->body);

        $insertData = [];
        $count = 0;
        $skippedRows = 0;

        foreach ($rows as $index => $row) {
            $name = $row['name'] ?? $row['Name'] ?? $row[0] ?? null;
            $phone = $row['phone'] ?? $row['Phone'] ?? $row[1] ?? null;

            $phone = $this->normalizePhone($phone);

            if (! $phone) {
                $skippedRows++;
                Log::warning('Skipped row due to invalid phone', [
                    'campaign_id' => $campaign->id,
                    'row_index' => $index,
                    'raw_phone' => $row['phone'] ?? $row[1] ?? null,
                ]);

                continue;
            }

            $variables = [];

            // Check if 'variables' column exists
            if (isset($row['variables']) && $row['variables']) {
                // Handle key:value pipe format: "kota:Semarang|profesi:Sales"
                if (is_string($row['variables']) && str_contains($row['variables'], ':') && str_contains($row['variables'], '|')) {
                    $pairs = explode('|', $row['variables']);
                    foreach ($pairs as $pair) {
                        if (str_contains($pair, ':')) {
                            [$key, $value] = explode(':', $pair, 2);
                            $variables[trim($key)] = trim($value);
                        }
                    }
                }
                // Handle JSON object format: {"kota":"Semarang","profesi":"Sales"}
                elseif (is_string($row['variables']) && str_starts_with(trim($row['variables']), '{')) {
                    $decoded = json_decode($row['variables'], true);
                    if (is_array($decoded)) {
                        $variables = $decoded;
                    }
                }
                // Handle pipe-delimited array format: "value1|value2|value3"
                elseif (is_string($row['variables']) && str_contains($row['variables'], '|')) {
                    $variables = explode('|', $row['variables']);
                }
                // Handle JSON array format: ["value1","value2"]
                elseif (is_string($row['variables']) && str_starts_with(trim($row['variables']), '[')) {
                    $variables = json_decode($row['variables'], true) ?? [];
                }
                // Handle array format
                elseif (is_array($row['variables'])) {
                    $variables = $row['variables'];
                }
                // Single value as string
                elseif (is_string($row['variables'])) {
                    $variables = [$row['variables']];
                }
            }
            // Check for custom column names (associative array)
            else {
                $excludeKeys = ['name', 'phone', 'Name', 'Phone', 0, 1];
                foreach ($row as $key => $value) {
                    if (! in_array($key, $excludeKeys, true) && $value !== null && $value !== '') {
                        $variables[$key] = $value;
                    }
                }
            }

            $insertData[] = [
                'broadcast_campaign_id' => $campaign->id,
                'name' => $name,
                'phone' => $phone,
                'variables' => json_encode($variables),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;
        }

        if ($count > 0) {
            BroadcastTarget::insert($insertData);
            $campaign->total_targets = $count;
            $campaign->save();

            Log::info('Broadcast targets processed successfully', [
                'campaign_id' => $campaign->id,
                'total_inserted' => $count,
                'skipped_rows' => $skippedRows,
            ]);
        } else {
            Log::warning('No valid targets to insert', [
                'campaign_id' => $campaign->id,
                'total_rows' => count($rows),
                'skipped_rows' => $skippedRows,
            ]);
        }
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
                'message' => 'Targets can only be uploaded while campaign is in DRAFT status.',
            ], 400);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $rows = $this->parseFile($request->file('file'));

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file is empty.',
            ], 422);
        }

        $template = $campaign->template;
        $requiredVars = $template->body_params_count ?? $this->detectVariables($template->body);

        $insertData = [];
        $count = 0;

        foreach ($rows as $row) {

            $name = $row['name'] ?? $row[0] ?? null;
            $phone = $row['phone'] ?? $row[1] ?? null;

            $phone = $this->normalizePhone($phone);
            if (! $phone) {
                continue;
            }

            $variables = $row['variables'] ?? [];
            if (! is_array($variables)) {
                $variables = [];
            }

            if ($requiredVars > 0 && count($variables) < $requiredVars) {
                return response()->json([
                    'success' => false,
                    'message' => "Row has invalid variable count for template. Required: {$requiredVars}",
                ], 422);
            }

            $insertData[] = [
                'broadcast_campaign_id' => $campaign->id,
                'name' => $name,
                'phone' => $phone,
                'variables' => json_encode($variables),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
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
                    'source' => 'broadcast_module',
                    'mode' => 'manual',
                ],
                'impact' => [
                    'added' => $count,
                    'total_targets' => $campaign->total_targets,
                ],
                'risk' => [
                    'level' => $riskLevel,
                    'reason' => $riskLevel !== 'low' ? 'Large target upload' : null,
                ],
            ]
        );

        return response()->json([
            'success' => true,
            'added' => $count,
            'message' => "Successfully added {$count} recipients.",
        ]);
    }

    /**
     * ===============================
     * DOWNLOAD SAMPLE CSV
     * ===============================
     */
    public function downloadSampleCsv()
    {
        $filename = 'sample-broadcast.csv';
        $filePath = storage_path('app/'.$filename);

        if (! file_exists($filePath)) {
            // Create sample file if not exists
            $content = "name,phone,kota,profesi\n";
            $content .= "John Doe,081234567890,Semarang,Sales\n";
            $content .= "Jane Smith,081298765432,Jakarta,Marketing\n";
            $content .= 'Bob Wilson,081222333444,Bandung,Developer';

            file_put_contents($filePath, $content);
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/csv',
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
                    'actor_id' => auth()->id(),
                    'actor_role' => auth()->user()->role ?? null,
                    'source' => 'broadcast_module',
                ],
                'filters' => $request->only('search', 'status'),
                'risk' => [
                    'level' => 'low',
                ],
            ]
        );

        return Inertia::render('Broadcast/Report', [
            'campaigns' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only('search', 'status'),
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

    private function parseFile($file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $rows = [];

        Log::info('Parsing file', [
            'extension' => $ext,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $firstRow = fgetcsv($handle, 0, ',');

            if ($firstRow === false) {
                fclose($handle);
                Log::error('Failed to read CSV file');

                return [];
            }

            $firstRow = array_map('trim', $firstRow);

            // Detect if first row is header or data
            // If first column is a phone number (all digits), treat as data without header
            $hasHeader = true;
            if (isset($firstRow[0])) {
                $firstCol = preg_replace('/[^0-9]/', '', $firstRow[0]);
                // If first column is numeric and looks like a phone (6+ digits), no header
                if (strlen($firstCol) >= 6 && ctype_digit($firstCol)) {
                    $hasHeader = false;
                }
            }

            if ($hasHeader) {
                // Use first row as headers
                $headers = $firstRow;
                Log::info('CSV has headers', ['headers' => $headers]);
            } else {
                // No headers, use default: phone, name, variables
                $headers = ['phone', 'name', 'variables'];
                Log::info('CSV has no headers, using defaults', ['headers' => $headers]);

                // Process first row as data
                $assocRow = [];
                foreach ($headers as $index => $header) {
                    $assocRow[$header] = $firstRow[$index] ?? null;
                }
                $rows[] = $assocRow;
            }

            // Read remaining rows
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $assocRow = [];
                foreach ($headers as $index => $header) {
                    $assocRow[$header] = $data[$index] ?? null;
                }
                $rows[] = $assocRow;
            }
            fclose($handle);

            Log::info('CSV parsed successfully', [
                'total_rows' => count($rows),
                'has_header' => $hasHeader,
                'headers' => $headers,
                'first_row_sample' => $rows[0] ?? null,
            ]);
        } else {
            Log::error('Failed to open CSV file');
        }

        return $rows;
    }

    private function normalizePhone($phone)
    {
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        if (strlen($phone) > 6) {
            return '62'.$phone;
        }

        return null;
    }
}
