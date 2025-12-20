<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\IamLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AgentController extends Controller
{
    /**
     * Allow only superadmin or admin.
     */
    private function ensureCanManageAgents(): void
    {
        $role = auth()->user()->role ?? null;

        if (!in_array($role, ['admin', 'superadmin'])) {
            abort(403, 'Only admin or superadmin can manage agents.');
        }
    }

    /**
     * List all agents (AUTO OFFLINE BASED ON last_seen)
     */
    public function index(Request $request)
    {
        $this->ensureCanManageAgents();

        $status = $request->query('status');

        $baseQuery = User::whereIn('role', ['agent', 'supervisor', 'admin']);

        if ($status && in_array($status, ['online', 'offline', 'pending'])) {
            $baseQuery->where('status', $status);
        }

        $agents = $baseQuery
            ->orderBy('name')
            ->get()
            ->map(function ($user) {

                // 🔥 PRESENCE RULE: 5 menit
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
                ];
            });

        $statsQuery = User::whereIn('role', ['agent', 'supervisor', 'admin']);

        $counts = [
            'all' => (clone $statsQuery)->count(),

            'online' => (clone $statsQuery)
                ->whereNotNull('last_seen')
                ->where('last_seen', '>=', now()->subMinutes(5))
                ->count(),

            'offline' => (clone $statsQuery)
                ->where(function ($q) {
                    $q->whereNull('last_seen')
                      ->orWhere('last_seen', '<', now()->subMinutes(5));
                })
                ->count(),

            'pending' => (clone $statsQuery)
                ->where('status', 'pending')
                ->count(),
        ];

        return Inertia::render('Agents/Index', [
            'agents'  => $agents,
            'counts'  => $counts,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Create new agent – AUTO GENERATE PASSWORD
     */
    public function store(Request $request)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'role'  => 'required|in:Admin,Supervisor,Agent',
        ]);

        $generatedPassword = str()->random(10);

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'role'        => strtolower($data['role']),
            'password'    => Hash::make($generatedPassword),
            'status'      => 'pending',
            'is_active'   => false,
            'approved_at' => null,
        ]);

        // 🔐 IAM AUDIT — CREATE USER
        IamLogger::log(
            'CREATE_USER',
            $user->id,
            null,
            $user->only(['email','role','status'])
        );

        return back()->with('password_generated', [
            'email'    => $data['email'],
            'password' => $generatedPassword,
        ]);
    }

    /**
     * Update existing agent.
     */
    public function update(Request $request, User $user)
    {
        $this->ensureCanManageAgents();

        $before = $user->only(['name','email','role']); // 🔐 IAM SNAPSHOT

        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|max:150|unique:users,email,' . $user->id,
            'role'             => 'required|in:Admin,Supervisor,Agent',
            'password'         => 'nullable|min:6',
            'password_confirm' => 'nullable|same:password',
        ]);

        $updateData = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => strtolower($data['role']),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // 🔐 IAM AUDIT — UPDATE ROLE / PROFILE
        IamLogger::log(
            'UPDATE_ROLE',
            $user->id,
            $before,
            $user->only(['name','email','role'])
        );

        return back()->with('success', 'Agent updated.');
    }

    /**
     * Approve agent.
     */
    public function approve(User $user)
    {
        $this->ensureCanManageAgents();

        $before = $user->only(['status','is_active','approved_at']); // 🔐 SNAPSHOT

        $user->update([
            'approved_at' => now(),
            'status'      => 'offline',
            'is_active'   => true,
        ]);

        // 🔐 IAM AUDIT — APPROVAL
        IamLogger::log(
            'APPROVE_USER',
            $user->id,
            $before,
            $user->only(['status','is_active','approved_at'])
        );

        return back()->with('success', 'Agent approved successfully.');
    }

    /**
     * Delete agent.
     */
    public function destroy(User $user)
    {
        $this->ensureCanManageAgents();

        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete yourself.'
            ], 422);
        }

        $before = $user->toArray(); // 🔐 SNAPSHOT

        $user->delete();

        // 🔐 IAM AUDIT — DELETE / REVOKE
        IamLogger::log(
            'DELETE_USER',
            $user->id,
            $before,
            null
        );

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * 🔔 HEARTBEAT (ONLINE)
     */
    public function heartbeat(Request $request)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['agent', 'supervisor'])) {
            return response()->json(['ignored' => true], 200);
        }

        $user->update([
            'status'    => 'online',
            'last_seen' => now(),
        ]);

        return response()->json(['ok' => true], 200);
    }

    /**
     * 📴 OFFLINE (LOGOUT / IDLE)
     */
    public function offline(Request $request)
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, ['agent', 'supervisor'])) {
            return response()->json(['ignored' => true], 200);
        }

        $user->update([
            'status' => 'offline',
        ]);

        return response()->json(['ok' => true], 200);
    }
}
