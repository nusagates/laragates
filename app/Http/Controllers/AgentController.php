<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        if (! in_array($role, ['admin', 'superadmin'])) {
            abort(403, 'Only admin or superadmin can manage agents.');
        }
    }

    /**
     * List all agents.
     */
    public function index(Request $request)
    {
        $this->ensureCanManageAgents();

        $status = $request->query('status');

        $baseQuery = User::whereIn('role', ['agent', 'supervisor', 'admin']);

        if ($status && in_array($status, ['online', 'offline', 'pending'])) {
            $baseQuery->where('status', $status);
        }

        $agents = $baseQuery->orderBy('name')->get()->map(function ($user) {
            return [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'status'      => $user->status,
                'approved_at' => $user->approved_at,
                'last_seen'   => $user->last_seen,
            ];
        });

        $statsQuery = User::whereIn('role', ['agent', 'supervisor', 'admin']);

        $counts = [
            'all'     => (clone $statsQuery)->count(),
            'online'  => (clone $statsQuery)->where('status', 'online')->count(),
            'offline' => (clone $statsQuery)->where('status', 'offline')->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
        ];

        return Inertia::render('Agents/Index', [
            'agents'  => $agents,
            'counts'  => $counts,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Create new agent – AUTO GENERATE PASSWORD.
     */
    public function store(Request $request)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'role'  => 'required|in:Admin,Supervisor,Agent',
        ]);

        // 🚀 Auto generate secure password
        $generatedPassword = str()->random(10);

        User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'role'        => strtolower($data['role']),
            'password'    => Hash::make($generatedPassword),
            'status'      => 'pending',
            'is_active'   => false,
            'approved_at' => null,
        ]);

        // 🚀 Flash password untuk ditampilkan di UI
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

        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'role'  => 'required|in:Admin,Supervisor,Agent',
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

        return back()->with('success', 'Agent updated.');
    }

    /**
     * Approve agent.
     */
    public function approve(User $user)
    {
        $this->ensureCanManageAgents();

        $user->update([
            'approved_at' => now(),
            'status'      => 'offline',
            'is_active'   => true,
        ]);

        return back()->with('success', 'Agent approved successfully.');
    }

    /**
     * Delete agent.
     */
    public function destroy(User $user)
    {
        $this->ensureCanManageAgents();

        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return back()->with('success', 'Agent deleted.');
    }
}
