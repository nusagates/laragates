<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class TemplateController extends Controller
{
    private function ensureCanManageTemplates()
    {
        $role = auth()->user()->role ?? null;

        if (! in_array($role, ['superadmin','admin','supervisor'])) {
            abort(403, 'Unauthorized');
        }
    }

    // LIST PAGE
    public function index(Request $request)
    {
        $this->ensureCanManageTemplates();

        $q = Template::query();

        if ($request->status) $q->where('status',$request->status);
        if ($request->search) {
            $s = $request->search;
            $q->where('name','like',"%$s%")
              ->orWhere('body','like',"%$s%");
        }

        $templates = $q->orderBy('created_at','desc')
                       ->paginate(20)->withQueryString();

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['search','status']),
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'language' => 'required|string|max:10',
            'header' => 'nullable|array',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
        ]);

        $this->validateTemplateRules($data);

        Template::create(array_merge($data, [
            'created_by' => Auth::id(),
            'status' => 'draft',
        ]));

        return back()->with('success','Template created.');
    }

    // SHOW DETAIL
    public function show(Template $template)
    {
        $this->ensureCanManageTemplates();

        return Inertia::render('Templates/Show', [
            'template' => $template
        ]);
    }

    // UPDATE
    public function update(Request $request, Template $template)
    {
        $this->ensureCanManageTemplates();

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'category' => 'nullable|string|max:50',
            'language' => 'required|string|max:10',
            'header' => 'nullable|array',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
        ]);

        $this->validateTemplateRules($data);

        $template->update(array_merge($data, [
            'version' => $template->version + 1,
            'status' => 'draft',
        ]));

        return back()->with('success','Updated.');
    }

    // DELETE
    public function destroy(Template $template)
    {
        $this->ensureCanManageTemplates();

        $template->delete();
        return back()->with('success','Deleted.');
    }

    // SUBMIT FOR APPROVAL
    public function submitForApproval(Template $template)
    {
        $this->ensureCanManageTemplates();

        $template->update([
            'status' => 'pending'
        ]);

        return back()->with('success','Template submitted.');
    }

    // APPROVE
    public function approve(Template $template)
    {
        $this->ensureCanManageTemplates();

        $template->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success','Approved.');
    }

    // REJECT
    public function reject(Template $template, Request $request)
    {
        $this->ensureCanManageTemplates();

        $reason = $request->input('reason');

        $template->update([
            'status' => 'rejected',
            'meta' => [
                'reason' => $reason,
                'rejected_by' => Auth::id()
            ]
        ]);

        return back()->with('success','Rejected.');
    }

    // SYNC TO PROVIDER
    public function sync(Template $template)
    {
        $this->ensureCanManageTemplates();

        if ($template->status !== 'approved') {
            return back()->with('error','Only approved template can sync.');
        }

        $payload = [
            'name' => $template->name,
            'language' => $template->language,
            'category' => $template->category ?? 'UTILITY',
            'components' => $this->buildComponents($template),
        ];

        $resp = Http::withToken(config('services.whatsapp.token'))
                ->post(config('services.whatsapp.api_base') . '/templates', $payload);

        if ($resp->successful()) {
            $data = $resp->json();
            $template->update([
                'status' => 'synced',
                'remote_id' => $data['id'] ?? null,
                'meta' => $data,
            ]);

            return back()->with('success','Synced.');
        }

        return back()->with('error','Failed: '.$resp->body());
    }

    private function buildComponents(Template $t)
    {
        $c = [];

        if ($t->header && ($t->header['type'] ?? 'none') !== 'none') {
            $c[] = [
                'type' => 'HEADER',
                'format' => strtoupper($t->header['type']),
                'text' => $t->header['content'] ?? null
            ];
        }

        $c[] = [
            'type' => 'BODY',
            'text' => $t->body
        ];

        if ($t->footer) {
            $c[] = [
                'type' => 'FOOTER',
                'text' => $t->footer
            ];
        }

        if ($t->buttons) {
            $c[] = [
                'type' => 'BUTTONS',
                'buttons' => $t->buttons
            ];
        }

        return $c;
    }

    private function validateTemplateRules($data)
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', $data['body'], $matches);
        if (count(array_unique($matches[1] ?? [])) > 10) {
            abort(422,'Max 10 variables allowed.');
        }
    }
}
