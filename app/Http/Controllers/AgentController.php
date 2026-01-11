<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\IamLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Services\AccountLockService;
use App\Services\SystemLogService;

class AgentController extends Controller
{
    private function ensureCanManageAgents(): void
    {
        $role = auth()->user()->role ?? null;

        if (!in_array($role, ['admin', 'superadmin'])) {
            abort(403, 'Only admin or superadmin can manage agents.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureCanManageAgents();

        $agents = User::whereIn('role', ['agent','supervisor','admin'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {

                $isOnline = $user->last_seen
                    && $user->last_seen->diffInMinutes(now()) <= 5;

                return [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'role'        => $user->role,
                    'status'      => $isOnline ? 'online' : 'offline',
                    'approved_at' => $user->approved_at,
                    'last_seen'   => $user->last_seen,

                    /* =========================
                     | HARDENING (EXISTING)
                     ========================= */
                    'locked_until'          => $user->locked_until,
                    'failed_login_attempts' => $user->failed_login_attempts,
                    'is_locked'             => $user->locked_until
                        && $user->locked_until->isFuture(),

                    /* =========================
                     | EMAIL VERIFICATION (BARU)
                     ========================= */
                    'email_verified_at'          => $user->email_verified_at,
                    'email_verify_grace_until'  => $user->email_verify_grace_until,
                    'verification_resend_count' => $user->verification_resend_count,
                ];
            });

        return Inertia::render('Agents/Index', [
            'agents' => $agents,
            'counts' => [
                'all'     => $agents->count(),
                'online'  => $agents->where('status','online')->count(),
                'offline' => $agents->where('status','offline')->count(),
                'pending' => $agents->whereNull('approved_at')->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'name'  => 'required',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|in:Admin,Supervisor,Agent',
        ]);

        $password = str()->random(10);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'role'     => strtolower($data['role']),
            'password' => Hash::make($password),
            'status'   => 'pending',
        ]);

        IamLogger::log(
            'CREATE_USER',
            $user->id,
            null,
            $user->only('email','role')
        );

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManageAgents();

        $before = $user->only(['name','email','role']);

        $data = $request->validate([
            'name'  => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:Admin,Supervisor,Agent',
        ]);

        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => strtolower($data['role']),
        ]);

        IamLogger::log(
            'UPDATE_ROLE',
            $user->id,
            $before,
            $user->only(['name','email','role'])
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'agent'   => $user
            ]);
        }

        return back()->with('success','Updated.');
    }

    public function approve(Request $request, User $user)
    {
        $this->ensureCanManageAgents();

        $before = $user->only(['status','approved_at']);

        $user->update([
            'approved_at' => now(),
            'status'      => 'offline',
        ]);

        IamLogger::log(
            'APPROVE_USER',
            $user->id,
            $before,
            $user->only(['status','approved_at'])
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'approved_at' => $user->approved_at
            ]);
        }

        return back()->with('success','Approved.');
    }

    public function destroy(User $user)
    {
        $this->ensureCanManageAgents();

        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'Cannot delete yourself'
            ], 422);
        }

        $before = $user->toArray();
        $user->delete();

        IamLogger::log(
            'DELETE_USER',
            $user->id,
            $before,
            null
        );

        return response()->json(['success'=>true]);
    }

    public function unlock(User $user)
    {
        AccountLockService::unlock($user);

        SystemLogService::record(
            'admin_unlock_account',
            'user',
            $user->id,
            null,
            null,
            ['by_admin' => auth()->id()]
        );

        return response()->json([
            'message' => 'Account unlocked',
        ]);
    }
}