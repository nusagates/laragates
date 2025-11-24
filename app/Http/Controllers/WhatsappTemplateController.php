<?php

namespace App\Http\Controllers;

use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class WhatsappTemplateController extends Controller
{
    /**
     * Display templates (UI or AJAX)
     */
    public function index()
    {
        $templates = WhatsappTemplate::all();

        // Jika request AJAX (axios, fetch)
        if (request()->wantsJson()) {
            return response()->json($templates);
        }

        // Jika request web biasa (Inertia)
        return Inertia::render('Templates/Index', [
            'templates' => $templates
        ]);
    }

    /**
     * Save new template
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
     * Show single template
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
     * Update template
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
     * Delete template
     */
    public function destroy(WhatsappTemplate $template)
    {
        $template->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Sync with WhatsApp Cloud API
     */
    public function sync()
    {
        $token = env('WABA_ACCESS_TOKEN');
        $phoneId = env('WABA_PHONE_NUMBER_ID');
        $version = env('WABA_API_VERSION', 'v21.0');

        $response = Http::withToken($token)->get("https://graph.facebook.com/{$version}/{$phoneId}/message_templates");

        if (!$response->successful()) {
            return response()->json(['error' => 'Failed to sync from Meta'], 500);
        }

        foreach ($response->json('data') ?? [] as $t) {
            WhatsappTemplate::updateOrCreate(
                ['name' => $t['name']],
                [
                    'category' => $t['category'] ?? null,
                    'language' => $t['language'] ?? 'id',
                    'status' => strtolower($t['status']),
                    'meta_id' => $t['id'],
                    'last_synced_at' => now(),
                    'header' => $t['components'][0]['text'] ?? null,
                    'body' => $t['components'][1]['text'] ?? '',
                    'footer' => $t['components'][2]['text'] ?? null,
                    'buttons' => $t['components'][3]['buttons'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Synced successfully']);
    }
}
