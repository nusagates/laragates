<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountLockService;
use App\Services\SystemLogService;
use App\Support\IamLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AgentController extends Controller
{
    private function ensureCanManageAgents(): void
    {
        $role = auth()->user()->role ?? null;

        if (! in_array($role, ['admin', 'superadmin'])) {
            abort(403, 'Only admin or superadmin can manage agents.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureCanManageAgents();

        $query = User::query()->where('role', 'agent');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->boolean('show_deleted')) {
            $query->withTrashed();
        }

        $agents = $query->orderBy('name')
            ->get()
            ->map(function ($agent) {
                $isOnline = $agent->last_seen && $agent->last_seen->diffInMinutes(now()) <= 5;

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'role' => $agent->role,
                    'status' => $isOnline ? 'online' : 'offline',
                    'approved_at' => $agent->approved_at,
                    'last_seen' => $agent->last_seen,
                    'locked_until' => $agent->locked_until,
                    'failed_login_attempts' => $agent->failed_login_attempts,
                    'is_locked' => $agent->locked_until && $agent->locked_until->isFuture(),
                    'company_id' => $agent->company_id,
                    'created_at' => $agent->created_at,
                    'deleted_at' => $agent->deleted_at,
                ];
            });

        return Inertia::render('Agents/Index', [
            'agents' => $agents,
            'counts' => [
                'all' => $agents->whereNull('deleted_at')->count(),
                'online' => $agents->where('status', 'online')->whereNull('deleted_at')->count(),
                'offline' => $agents->where('status', 'offline')->whereNull('deleted_at')->count(),
                'pending' => $agents->whereNull('approved_at')->whereNull('deleted_at')->count(),
                'locked' => $agents->where('is_locked', true)->whereNull('deleted_at')->count(),
                'deleted' => $agents->whereNotNull('deleted_at')->count(),
            ],
            'filters' => $request->only(['search', 'show_deleted']),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $password = str()->random(12);

        $agent = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'agent',
            'password' => Hash::make($password),
            'status' => 'pending',
            'email_verified_at' => now(),
            'company_id' => $data['company_id'] ?? auth()->user()->company_id,
        ]);

        IamLogger::log('CREATE_USER', $agent->id, null, $agent->only('email', 'role', 'company_id'));

        return response()->json([
            'success' => true,
            'agent' => $agent,
            'temp_password' => $password,
        ]);
    }

    public function update(Request $request, User $agent)
    {
        $this->ensureCanManageAgents();

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        $before = $agent->only(['name', 'email', 'company_id']);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$agent->id,
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $agent->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'company_id' => $data['company_id'] ?? $agent->company_id,
        ]);

        IamLogger::log('UPDATE_USER', $agent->id, $before, $agent->only(['name', 'email', 'company_id']));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'agent' => $agent,
            ]);
        }

        return back()->with('success', 'Agent updated.');
    }

    public function approve(Request $request, User $agent)
    {
        $this->ensureCanManageAgents();

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        $before = $agent->only(['status', 'approved_at']);

        $agent->update([
            'approved_at' => now(),
            'status' => 'offline',
        ]);

        IamLogger::log('APPROVE_USER', $agent->id, $before, $agent->only(['status', 'approved_at']));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'approved_at' => $agent->approved_at,
            ]);
        }

        return back()->with('success', 'Agent approved.');
    }

    public function destroy(User $agent)
    {
        $this->ensureCanManageAgents();

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        if (auth()->id() === $agent->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 422);
        }

        $before = $agent->toArray();
        $agent->delete();

        IamLogger::log('DELETE_USER', $agent->id, $before, null);

        return response()->json(['success' => true]);
    }

    public function forceDestroy(int $id)
    {
        $this->ensureCanManageAgents();

        $agent = User::withTrashed()->findOrFail($id);

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        if (auth()->id() === $agent->id) {
            return response()->json(['message' => 'Cannot permanently delete yourself'], 422);
        }

        $before = $agent->toArray();
        $agent->forceDelete();

        IamLogger::log('FORCE_DELETE_USER', $agent->id, $before, null);

        SystemLogService::record(
            'agent_force_deleted',
            'user',
            $agent->id,
            null,
            null,
            ['by_admin' => auth()->id()]
        );

        return response()->json(['success' => true]);
    }

    public function restore(int $id)
    {
        $this->ensureCanManageAgents();

        $agent = User::withTrashed()->findOrFail($id);

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        $agent->restore();

        IamLogger::log('RESTORE_USER', $agent->id, ['deleted_at' => $agent->deleted_at], null);

        return response()->json(['success' => true]);
    }

    public function lock(User $agent)
    {
        $this->ensureCanManageAgents();

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        $agent->update([
            'locked_until' => now()->addHours(24),
            'failed_login_attempts' => 6,
        ]);

        IamLogger::log('LOCK_USER', $agent->id, ['locked' => false], ['locked' => true, 'locked_until' => $agent->locked_until]);

        SystemLogService::record(
            'admin_lock_account',
            'user',
            $agent->id,
            null,
            null,
            ['by_admin' => auth()->id()]
        );

        return response()->json([
            'message' => 'Agent account locked',
        ]);
    }

    public function unlock(User $agent)
    {
        $this->ensureCanManageAgents();

        if ($agent->role !== 'agent') {
            return response()->json(['message' => 'User is not an agent'], 422);
        }

        AccountLockService::unlock($agent);

        SystemLogService::record(
            'admin_unlock_account',
            'user',
            $agent->id,
            null,
            null,
            ['by_admin' => auth()->id()]
        );

        return response()->json([
            'message' => 'Agent account unlocked',
        ]);
    }
}
