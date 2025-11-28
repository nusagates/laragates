<?php

namespace App\Http\Controllers;

use App\Models\WhatsappTemplate;
use App\Models\TemplateVersion;
use App\Models\TemplateApprovalNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WhatsappTemplateController extends Controller
{
    // ----------------------------------------------------------------------
    // UI PAGE
    // ----------------------------------------------------------------------
    public function index()
    {
        return Inertia::render('Templates/Index');
    }

    // LIST (JSON)
    public function list()
    {
        return response()->json(
            WhatsappTemplate::orderBy('updated_at', 'desc')->get()
        );
    }

    // ----------------------------------------------------------------------
    // CREATE TEMPLATE
    // ----------------------------------------------------------------------
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|unique:whatsapp_templates,name',
            'category' => 'required|string',
            'language' => 'required|string',
            'header'   => 'nullable|string',
            'body'     => 'required|string',
            'footer'   => 'nullable|string',
            'buttons'  => 'nullable'
        ]);

        if (is_string($data['buttons'] ?? null)) {
            $data['buttons'] = json_decode($data['buttons'], true) ?? null;
        }

        $template = WhatsappTemplate::create($data + [
            'status'     => 'draft',
            'created_by' => Auth::id(),
        ]);

        return response()->json($template);
    }

    // ----------------------------------------------------------------------
    // SHOW TEMPLATE
    // ----------------------------------------------------------------------
    public function show(WhatsappTemplate $template)
    {
        $versions = TemplateVersion::where('template_id', $template->id)
            ->orderBy('created_at', 'desc')->get();

        $notes = TemplateApprovalNote::where('template_id', $template->id)
            ->orderBy('created_at', 'desc')->get();

        if (request()->wantsJson()) {
            return response()->json([
                'template' => $template,
                'versions' => $versions,
                'notes'    => $notes,
            ]);
        }

        return Inertia::render('Templates/Show', [
            'template' => $template,
            'versions' => $versions,
            'notes'    => $notes,
        ]);
    }

    // ----------------------------------------------------------------------
    // UPDATE TEMPLATE
    // ----------------------------------------------------------------------
    public function update(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'name'     => 'required|string|unique:whatsapp_templates,name,' . $template->id,
            'category' => 'required|string',
            'language' => 'required|string',
            'header'   => 'nullable|string',
            'body'     => 'required|string',
            'footer'   => 'nullable|string',
            'buttons'  => 'nullable',
        ]);

        if (is_string($data['buttons'] ?? null)) {
            $data['buttons'] = json_decode($data['buttons'], true) ?? null;
        }

        $template->update($data);

        return response()->json($template);
    }

    // ----------------------------------------------------------------------
    // DELETE TEMPLATE
    // ----------------------------------------------------------------------
    public function destroy(WhatsappTemplate $template)
    {
        $template->delete();
        return response()->json(['deleted' => true]);
    }

    // ----------------------------------------------------------------------
    // WORKFLOW: SUBMIT FOR APPROVAL
    // ----------------------------------------------------------------------
    public function submit(WhatsappTemplate $template)
    {
        $template->update(['status' => 'submitted']);

        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id'     => Auth::id(),
            'note'        => 'Submitted for approval'
        ]);

        return response()->json(['ok' => true]);
    }

    // APPROVE
    public function approve(Request $request, WhatsappTemplate $template)
    {
        $note = $request->input('note', 'Approved');

        $template->update([
            'status'       => 'approved',
            'approved_at'  => now(),
            'approved_by'  => Auth::id(),
        ]);

        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id'     => Auth::id(),
            'note'        => $note,
        ]);

        return response()->json(['ok' => true]);
    }

    // REJECT
    public function reject(Request $request, WhatsappTemplate $template)
    {
        $reason = $request->input('reason', 'Rejected');

        $template->update(['status' => 'rejected']);

        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id'     => Auth::id(),
            'note'        => $reason,
        ]);

        return response()->json(['ok' => true]);
    }

    // ----------------------------------------------------------------------
    // VERSIONING
    // ----------------------------------------------------------------------
    public function createVersion(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'header'  => 'nullable|string',
            'body'    => 'required|string',
            'footer'  => 'nullable|string',
            'buttons' => 'nullable',
        ]);

        TemplateVersion::create([
            'template_id'   => $template->id,
            'header'        => $data['header'],
            'body'          => $data['body'],
            'footer'        => $data['footer'],
            'buttons'       => $data['buttons'] ?? null,
            'user_id'       => Auth::id(),
            'version_label' => 'v' . (TemplateVersion::where('template_id', $template->id)->count() + 1)
        ]);

        return response()->json(['ok' => true]);
    }

    public function revertVersion(WhatsappTemplate $template, TemplateVersion $version)
    {
        if ($version->template_id !== $template->id) {
            return response()->json(['error' => 'Version mismatch'], 400);
        }

        $template->update([
            'header'  => $version->header,
            'body'    => $version->body,
            'footer'  => $version->footer,
            'buttons' => $version->buttons,
        ]);

        TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id'     => Auth::id(),
            'note'        => 'Reverted to version ' . $version->version_label,
        ]);

        return response()->json(['ok' => true]);
    }

    // ----------------------------------------------------------------------
    // NOTES
    // ----------------------------------------------------------------------
    public function addNote(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate(['note' => 'required|string']);

        $note = TemplateApprovalNote::create([
            'template_id' => $template->id,
            'user_id'     => Auth::id(),
            'note'        => $data['note'],
        ]);

        return response()->json($note);
    }

    // ----------------------------------------------------------------------
    // FINAL — SYNC USING WABASERVICE (SINGLE TEMPLATE)
    // ----------------------------------------------------------------------
    public function syncSingle(WhatsappTemplate $template)
    {
        $remoteId = $template->remote_id ?? $template->name;

        try {
            $apiTemplate = app(\App\Services\WabaService::class)->getTemplate($remoteId);

            if (!$apiTemplate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found on Meta API'
                ], 404);
            }

            $payload = app(\App\Services\WabaService::class)
                ->normalizeTemplatePayload($apiTemplate);

            $template->update($payload);

            return response()->json([
                'success'  => true,
                'template' => $template->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
