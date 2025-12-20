<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateNote;
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

        // supervisor tidak boleh approve
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

        if ($request->status) $q->where('status',$request->status);

        if ($request->search) {
            $s = $request->search;
            $q->where(fn($x) =>
                $x->where('name','like',"%$s%")
                  ->orWhere('body','like',"%$s%")
            );
        }

        $templates = $q->orderBy('created_at','desc')
                       ->paginate(20)->withQueryString();

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

        Template::create(array_merge($data, [
            'created_by' => Auth::id(),
            'status'     => 'draft',
        ]));

        return back()->with('success','Template created.');
    }

    /* -----------------------------------------------------
     | SHOW DETAIL (with versions & notes)
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

    $template->update(array_merge($data, [
        'version' => $template->version + 1,
        'status'  => 'draft',
    ]));

    return response()->json([
        'message'  => 'Template updated',
        'template' => $template->fresh()
    ]);
}


    /* -----------------------------------------------------
     | DELETE
     ------------------------------------------------------*/
    public function destroy(Template $template)
{
    $this->ensureCanManageTemplates();

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

        $template->update([
            'status' => 'submitted'
        ]);

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
            'name'      => $template->name,
            'language'  => $template->language,
            'category'  => $template->category ?? 'UTILITY',
            'components'=> $this->buildComponents($template),
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

        return back()->with('success','Synced.');
    }

    private function buildComponents(Template $t)
    {
        $c = [];

        if (!empty($t->header)) {
            $c[] = [
                'type'   => 'HEADER',
                'format' => 'TEXT',
                'text'   => $t->header
            ];
        }

        $c[] = [
            'type' => 'BODY',
            'text' => $t->body
        ];

        if (!empty($t->footer)) {
            $c[] = [
                'type' => 'FOOTER',
                'text' => $t->footer
            ];
        }

        if ($t->buttons) {
            $c[] = [
                'type'    => 'BUTTONS',
                'buttons' => $t->buttons
            ];
        }

        return $c;
    }

    /* -----------------------------------------------------
     | SEND TEMPLATE (SIMULASI ATAU CUSTOM PROVIDER)
     ------------------------------------------------------*/
    public function send(Template $template, Request $request)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'to'        => 'required|string',
            'language'  => 'required|string',
            'components'=> 'nullable|array'
        ]);

        // Simulasi kirim (bisa ganti dengan Fonnte/Provider)
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

        return response()->json([
            'message' => 'Version saved'
        ]);
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

        return response()->json([
            'message' => 'Reverted to selected version'
        ]);
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

        return response()->json([
            'message' => 'Note saved'
        ]);
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
