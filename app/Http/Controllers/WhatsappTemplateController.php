<?php

namespace App\Http\Controllers;

use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class WhatsappTemplateController extends Controller
{
    /**
     * Display templates (Inertia) or JSON.
     */
    public function index()
    {
        $templates = WhatsappTemplate::orderBy('updated_at', 'desc')->get();

        if (request()->wantsJson()) {
            return response()->json($templates);
        }

        return Inertia::render('Templates/Index', [
            'templates' => $templates
        ]);
    }

    /**
     * API: list (JSON) for Vue (if you named /templates/list or /templates/all)
     */
    public function list()
    {
        return response()->json(WhatsappTemplate::orderBy('updated_at', 'desc')->get());
    }

    /**
     * Store new template (local DB)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:whatsapp_templates,name',
            'category' => 'required|string',
            'language' => 'required|string',
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
        ]);

        $template = WhatsappTemplate::create($data);

        return response()->json($template);
    }

    /**
     * Show template (Inertia or JSON)
     */
    public function show(WhatsappTemplate $template)
    {
        if (request()->wantsJson()) {
            return response()->json($template);
        }

        return Inertia::render('Templates/Show', [
            'template' => $template
        ]);
    }

    /**
     * Update
     */
    public function update(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:whatsapp_templates,name,' . $template->id,
            'category' => 'required|string',
            'language' => 'required|string',
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
        ]);

        $template->update($data);

        return response()->json($template);
    }

    /**
     * Destroy
     */
    public function destroy(WhatsappTemplate $template)
    {
        $template->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Sync (list from Meta) — keep existing behavior (reads templates metadata from Meta)
     */
    public function sync()
    {
        $token = env('WABA_ACCESS_TOKEN');
        $phoneId = env('WABA_PHONE_NUMBER_ID');
        $version = env('WABA_API_VERSION', 'v21.0');

        if (! $token || ! $phoneId) {
            return response()->json(['error' => 'WABA credentials not configured'], 500);
        }

        $response = Http::withToken($token)->get("https://graph.facebook.com/{$version}/{$phoneId}/message_templates");

        if (! $response->successful()) {
            return response()->json(['error' => 'Failed to sync from Meta', 'details' => $response->body()], 500);
        }

        foreach ($response->json('data') ?? [] as $t) {
            WhatsappTemplate::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category' => $t['category'] ?? null,
                    'language' => $t['language'] ?? 'id',
                    'status' => strtolower($t['status'] ?? 'approved'),
                    'meta_id' => $t['id'] ?? null,
                    'last_synced_at' => now(),
                    'header' => $t['components'][0]['text'] ?? null,
                    'body' => $t['components'][1]['text'] ?? ($t['components'][0]['text'] ?? ''),
                    'footer' => $t['components'][2]['text'] ?? null,
                    'buttons' => $t['components'][3]['buttons'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Synced successfully']);
    }

    /**
     * SUBMIT for approval (local workflow)
     */
    public function submit(WhatsappTemplate $template)
    {
        $template->update(['status' => 'submitted']);
        return response()->json(['ok' => true]);
    }

    /**
     * Approve (local workflow)
     */
    public function approve(WhatsappTemplate $template)
    {
        $template->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        return response()->json(['ok' => true]);
    }

    /**
     * Reject (local workflow)
     */
    public function reject(Request $request, WhatsappTemplate $template)
    {
        $reason = $request->input('reason', null);
        $template->update(['status' => 'rejected', 'workflow_notes' => $reason]);
        return response()->json(['ok' => true]);
    }

    /**
     * SEND template to a phone number (via WhatsApp Cloud API).
     *
     * Request payload example:
     * {
     *   "to": "6281234567890",
     *   "language": "id", // optional
     *   "components": [
     *       {"type":"header","parameters":[{"type":"text","text":"Header value"}]},
     *       {"type":"body","parameters":[{"type":"text","text":"Alice"}]},
     *       {"type":"button","sub_type":"quick_reply","index":0}
     *   ]
     * }
     */
    public function send(Request $request, WhatsappTemplate $template)
    {
        $validator = Validator::make($request->all(), [
            'to' => ['required','string'],
            'language' => ['nullable','string'],
            'components' => ['nullable','array'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed','messages' => $validator->errors()], 422);
        }

        $to = $request->input('to');
        $language = $request->input('language', $template->language ?? 'id');
        $components = $request->input('components', null);

        // If template has meta_id, send as template message to WhatsApp Cloud API
        $metaId = $template->meta_id;

        $token = env('WABA_ACCESS_TOKEN');
        $phoneId = env('WABA_PHONE_NUMBER_ID');
        $version = env('WABA_API_VERSION', 'v21.0');

        if ($metaId && $token && $phoneId) {
            // Build payload for template message
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $template->name, // If Meta expects name not metaId
                    'language' => ['code' => $language],
                ]
            ];

            // If components provided (overriding), attach
            if ($components && is_array($components)) {
                $payload['template']['components'] = $components;
            } else {
                // Try to prepare body parameters from placeholders {1}, {2}, ... (basic)
                // This attempts to parse placeholders in body like {1} and send them as text params
                preg_match_all('/\{(\d+)\}/', $template->body, $matches);
                if (!empty($matches[1])) {
                    $params = [];
                    foreach ($matches[1] as $index) {
                        $params[] = ['type' => 'text', 'text' => ""]; // placeholder empty, FE should send components ideally
                    }
                    if ($params) {
                        $payload['template']['components'] = [
                            ['type' => 'body', 'parameters' => $params]
                        ];
                    }
                }
            }

            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", $payload);

            if (! $response->successful()) {
                return response()->json(['error' => 'Failed to send via WABA','details'=>$response->json()], 500);
            }

            // update last_sent_at
            $template->update(['last_sent_at' => now()]);

            return response()->json(['sent' => true, 'response' => $response->json()]);
        }

        // FALLBACK: if no meta_id / no WABA credentials -> send plain text (dev/testing)
        try {
            $text = $template->body;
            // replace placeholders with nothing (since we don't have params)
            $text = preg_replace('/\{\d+\}/', '', $text);

            // If token/phoneId missing -> cannot call WABA, return fallback result
            if (! $token || ! $phoneId) {
                // log or simply return.
                return response()->json(['warning' => 'WABA not configured, returning plain text payload', 'text' => $text]);
            }

            // If we have token+phoneId but meta_id missing, we send as text message using messages endpoint
            $plainPayload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $text],
            ];

            $response = Http::withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", $plainPayload);

            if (! $response->successful()) {
                return response()->json(['error' => 'Failed to send plain text via WABA','details'=>$response->json()], 500);
            }

            $template->update(['last_sent_at' => now()]);
            return response()->json(['sent' => true, 'response' => $response->json()]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Send failed', 'message' => $e->getMessage()], 500);
        }
    }
}
