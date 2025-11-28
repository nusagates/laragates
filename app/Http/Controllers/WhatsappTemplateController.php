<?php

namespace App\Http\Controllers;

use App\Models\WhatsappTemplate;
use App\Models\TemplateVersion;
use App\Models\TemplateApprovalNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WhatsappTemplateController extends Controller
{
    // UI page (Inertia)
    public function index()
    {
        // If AJAX return later via /templates-list
        return Inertia::render('Templates/Index');
    }

    // API: return all templates as JSON for Vue
    public function list()
    {
        $templates = WhatsappTemplate::orderBy('updated_at','desc')->get();
        return response()->json($templates);
    }

    // Store
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:whatsapp_templates,name',
            'category' => 'required|string',
            'language' => 'required|string',
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable'
        ]);

        // Normalize buttons if JSON string passed
        if (is_string($data['buttons'] ?? null)) {
            try { $data['buttons'] = json_decode($data['buttons'], true); } catch (\Throwable $e) { $data['buttons'] = null; }
        }

        $t = WhatsappTemplate::create($data + ['status' => 'draft', 'created_by' => Auth::id()]);
        return response()->json($t);
    }

    // Show (return JSON for Vue)
    public function show(WhatsappTemplate $template)
    {
        if (request()->wantsJson()) {
            $versions = TemplateVersion::where('template_id',$template->id)->orderBy('created_at','desc')->get();
            $notes = TemplateApprovalNote::where('template_id',$template->id)->orderBy('created_at','desc')->get();
            return response()->json([
                'template' => $template,
                'versions' => $versions,
                'notes' => $notes
            ]);
        }

        return Inertia::render('Templates/Show', [
            'template' => $template,
            'versions' => TemplateVersion::where('template_id',$template->id)->orderBy('created_at','desc')->get(),
            'notes' => TemplateApprovalNote::where('template_id',$template->id)->orderBy('created_at','desc')->get()
        ]);
    }

    // Update
    public function update(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:whatsapp_templates,name,' . $template->id,
            'category' => 'required|string',
            'language' => 'required|string',
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable'
        ]);

        if (is_string($data['buttons'] ?? null)) {
            try { $data['buttons'] = json_decode($data['buttons'], true); } catch (\Throwable $e) { $data['buttons'] = null; }
        }

        $template->update($data + ['created_by' => $template->created_by ?? Auth::id()]);
        return response()->json($template);
    }

    // Delete
    public function destroy(WhatsappTemplate $template)
    {
        $template->delete();
        return response()->json(['deleted' => true]);
    }

    // SYNC: keep your previous implementation
    public function sync(WhatsappTemplate $template)
    {
        $token = env('WABA_ACCESS_TOKEN');
        $phoneId = env('WABA_PHONE_NUMBER_ID');
        $version = env('WABA_API_VERSION', 'v21.0');

        if (!$token || !$phoneId) {
            return response()->json(['error' => 'WABA credentials not configured'], 400);
        }

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

    // ---------------- workflow ----------------

    // submit for approval (user)
    public function submit(WhatsappTemplate $template)
    {
        $template->update(['status' => 'submitted']);
        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id' => Auth::id(),
            'note' => 'Submitted for approval'
        ]);
        return response()->json(['ok' => true]);
    }

    // approve (superadmin)
    public function approve(Request $request, WhatsappTemplate $template)
    {
        $note = $request->input('note', 'Approved');
        $template->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => Auth::id()]);
        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id' => Auth::id(),
            'note' => $note
        ]);
        return response()->json(['ok' => true]);
    }

    // reject (superadmin)
    public function reject(Request $request, WhatsappTemplate $template)
    {
        $reason = $request->input('reason', 'Rejected');
        $template->update(['status' => 'rejected']);
        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id' => Auth::id(),
            'note' => $reason
        ]);
        return response()->json(['ok' => true]);
    }

    // ---------------- versions ----------------

    // create version snapshot
    public function createVersion(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable'
        ]);

        TemplateVersion::create([
            'template_id' => $template->id,
            'header' => $data['header'],
            'body' => $data['body'],
            'footer' => $data['footer'],
            'buttons' => $data['buttons'] ?? null,
            'user_id' => Auth::id(),
            'version_label' => 'v' . (TemplateVersion::where('template_id',$template->id)->count() + 1)
        ]);

        return response()->json(['ok' => true]);
    }

    // revert to version
    public function revertVersion(WhatsappTemplate $template, TemplateVersion $version)
    {
        // only allow versions that belong to template
        if ($version->template_id !== $template->id) {
            return response()->json(['error' => 'Version mismatch'], 400);
        }

        $template->update([
            'header' => $version->header,
            'body' => $version->body,
            'footer' => $version->footer,
            'buttons' => $version->buttons
        ]);

        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id' => Auth::id(),
            'note' => 'Reverted to version ' . $version->id
        ]);

        return response()->json(['ok' => true]);
    }

    // ---------------- notes ----------------
    public function addNote(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate(['note' => 'required|string']);
        $n = TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id' => Auth::id(),
            'note' => $data['note']
        ]);
        return response()->json($n);
    }
}
