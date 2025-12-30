<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateNote;
use App\Services\SystemLogService;
use App\Support\TemplateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class TemplateController extends Controller
{
    /* -----------------------------------------------------
     | ROLE VALIDATION
     ------------------------------------------------------*/
    private function ensureCanManageTemplates()
    {
        $role = auth()->user()->role ?? null;

        if (! in_array($role, ['superadmin','admin','supervisor'])) {
            abort(403, 'Unauthorized');
        }
    }

    private function ensureCanApproveTemplates()
    {
        $role = auth()->user()->role ?? null;

        if (! in_array($role, ['superadmin','admin'])) {
            abort(403, 'You are not allowed to approve templates.');
        }
    }

    /* -----------------------------------------------------
     | INDEX
     ------------------------------------------------------*/
    public function index(Request $request)
    {
        $this->ensureCanManageTemplates();

        $q = Template::query();

        if ($request->status) {
            $q->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $q->where(fn ($x) =>
                $x->where('name', 'like', "%$s%")
                  ->orWhere('body', 'like', "%$s%")
            );
        }

        $templates = $q->orderBy('created_at','desc')
                       ->paginate(20)
                       ->withQueryString();

        SystemLogService::record(
            'template_view',
            null,
            null,
            null,
            null,
            [
                'description' => 'Opened template list',
                'filters'     => $request->only(['search','status']),
            ]
        );

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'filters'   => $request->only(['search','status']),
        ]);
    }

    /* -----------------------------------------------------
     | CREATE
     ------------------------------------------------------*/
    public function store(Request $request)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'name'     => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'language' => 'required|string|max:10',
            'header'   => 'nullable|string',
            'body'     => 'required|string',
            'footer'   => 'nullable|string',
            'buttons'  => 'nullable|array',
        ]);

        $this->validateTemplateRules($data);

        $template = Template::create(array_merge($data, [
            'created_by' => Auth::id(),
            'status'     => 'draft',
        ]));

        SystemLogService::record(
            'template_create',
            'template',
            $template->id,
            null,
            $template->toArray(),
            TemplateLog::meta(
                $template,
                'create',
                'Template "' . $template->name . '" created'
            )
        );

        return back()->with('success','Template created.');
    }

    /* -----------------------------------------------------
     | SHOW
     ------------------------------------------------------*/
    public function show(Template $template)
    {
        $this->ensureCanManageTemplates();

        return Inertia::render('Templates/Show', [
            'template' => $template,
            'versions' => $template->versions()->orderBy('id','desc')->get(),
            'notes'    => $template->notes()->orderBy('id','desc')->with('user')->get(),
        ]);
    }

    /* -----------------------------------------------------
     | UPDATE
     ------------------------------------------------------*/
    public function update(Request $request, Template $template)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'name'     => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'language' => 'required|string|max:10',
            'header'   => 'nullable|string',
            'body'     => 'required|string',
            'footer'   => 'nullable|string',
            'buttons'  => 'nullable|array',
        ]);

        $this->validateTemplateRules($data);

        $old = $template->toArray();

        $template->update(array_merge($data, [
            'version' => $template->version + 1,
            'status'  => 'draft',
        ]));

        SystemLogService::record(
            'template_update',
            'template',
            $template->id,
            $old,
            $template->fresh()->toArray(),
            TemplateLog::meta(
                $template,
                'update',
                'Template "' . $template->name . '" updated'
            )
        );

        return response()->json([
            'message'  => 'Template updated',
            'template' => $template->fresh()
        ]);
    }

    /* -----------------------------------------------------
     | DELETE (HIGH RISK)
     ------------------------------------------------------*/
    public function destroy(Template $template)
    {
        $this->ensureCanManageTemplates();

        SystemLogService::record(
            'template_delete',
            'template',
            $template->id,
            $template->toArray(),
            null,
            TemplateLog::highRisk(
                $template,
                'delete',
                'Template "' . $template->name . '" deleted'
            )
        );

        $template->delete();

        return response()->json([
            'message' => 'Template deleted',
            'id'      => $template->id
        ]);
    }

    /* -----------------------------------------------------
     | SUBMIT FOR APPROVAL
     ------------------------------------------------------*/
    public function submitForApproval(Template $template)
    {
        $this->ensureCanManageTemplates();

        $template->update(['status' => 'submitted']);

        SystemLogService::record(
            'template_submit',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::meta(
                $template,
                'submit',
                'Template "' . $template->name . '" submitted for approval'
            )
        );

        return back()->with('success','Submitted for approval.');
    }

    /* -----------------------------------------------------
     | APPROVE
     ------------------------------------------------------*/
    public function approve(Template $template)
    {
        $this->ensureCanApproveTemplates();

        $template->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        SystemLogService::record(
            'template_approve',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::approval(
                $template,
                'approve',
                'Template "' . $template->name . '" approved'
            )
        );

        return back()->with('success','Approved.');
    }

    /* -----------------------------------------------------
     | REJECT
     ------------------------------------------------------*/
    public function reject(Template $template, Request $request)
    {
        $this->ensureCanApproveTemplates();

        $reason = $request->input('reason','No reason provided');

        $template->update([
            'status' => 'rejected',
            'meta'   => [
                'reason'      => $reason,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
            ]
        ]);

        SystemLogService::record(
            'template_reject',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::meta(
                $template,
                'reject',
                'Template "' . $template->name . '" rejected',
                ['reason' => $reason]
            )
        );

        return back()->with('success','Rejected.');
    }

    /* -----------------------------------------------------
     | SYNC KE META
     ------------------------------------------------------*/
    public function sync(Template $template)
    {
        $this->ensureCanApproveTemplates();

        if ($template->status !== 'approved') {
            return back()->with('error','Only approved templates can sync.');
        }

        $payload = [
            'name'       => $template->name,
            'language'   => $template->language,
            'category'   => $template->category ?? 'UTILITY',
            'components' => $this->buildComponents($template),
        ];

        $resp = Http::withToken(config('services.whatsapp.token'))
            ->post(config('services.whatsapp.api_base') . '/templates', $payload);

        if (! $resp->successful()) {
            return back()->with('error', 'Failed: '.$resp->body());
        }

        $data = $resp->json();

        $template->update([
            'status'    => 'synced',
            'remote_id' => $data['id'] ?? null,
            'meta'      => $data,
        ]);

        SystemLogService::record(
            'template_sync',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::sync(
                $template,
                'meta',
                'Template "' . $template->name . '" synced to Meta',
                ['remote_id' => $data['id'] ?? null]
            )
        );

        return back()->with('success','Synced.');
    }

    /* -----------------------------------------------------
     | SEND TEMPLATE
     ------------------------------------------------------*/
    public function send(Template $template, Request $request)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'to'        => 'required|string',
            'language'  => 'required|string',
            'components'=> 'nullable|array'
        ]);

        SystemLogService::record(
            'template_send',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::meta(
                $template,
                'send',
                'Template "' . $template->name . '" sent',
                [
                    'to'       => $data['to'],
                    'language' => $data['language']
                ]
            )
        );

        return response()->json([
            'status'  => 'ok',
            'message' => 'Send simulated OK',
            'payload' => $data,
        ]);
    }

    /* -----------------------------------------------------
     | SAVE VERSION
     ------------------------------------------------------*/
    public function saveVersion(Template $template, Request $request)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'header'  => 'nullable|string',
            'body'    => 'required|string',
            'footer'  => 'nullable|string',
            'buttons' => 'nullable|array',
        ]);

        $template->versions()->create($data);

        SystemLogService::record(
            'template_version_save',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::meta(
                $template,
                'version_save',
                'New version saved for template "' . $template->name . '"'
            )
        );

        return response()->json(['message' => 'Version saved']);
    }

    /* -----------------------------------------------------
     | REVERT VERSION
     ------------------------------------------------------*/
    public function revertVersion(Template $template, $versionId)
    {
        $this->ensureCanManageTemplates();

        $version = $template->versions()->findOrFail($versionId);

        $template->update([
            'header'  => $version->header,
            'body'    => $version->body,
            'footer'  => $version->footer,
            'buttons' => $version->buttons,
            'version' => $template->version + 1,
        ]);

        SystemLogService::record(
            'template_version_revert',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::meta(
                $template,
                'version_revert',
                'Template "' . $template->name . '" reverted to previous version',
                ['version_id' => $versionId]
            )
        );

        return response()->json(['message' => 'Reverted to selected version']);
    }

    /* -----------------------------------------------------
     | ADD NOTE
     ------------------------------------------------------*/
    public function saveNote(Template $template, Request $request)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'note' => 'required|string'
        ]);

        $template->notes()->create([
            'note'       => $data['note'],
            'created_by' => Auth::id()
        ]);

        SystemLogService::record(
            'template_note_add',
            'template',
            $template->id,
            null,
            null,
            TemplateLog::meta(
                $template,
                'note_add',
                'Note added to template "' . $template->name . '"'
            )
        );

        return response()->json(['message' => 'Note saved']);
    }

    /* -----------------------------------------------------
     | VALIDATOR
     ------------------------------------------------------*/
    private function validateTemplateRules($data)
    {
        preg_match_all('/\{(\d+)\}/', $data['body'], $matches);

        if (count(array_unique($matches[1] ?? [])) > 10) {
            abort(422,'Max 10 variables allowed.');
        }
    }
}
