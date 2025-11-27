<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AgentController extends Controller
{
    /**
     * Pastikan hanya admin / supervisor yang boleh manage agents.
     */
    private function ensureCanManageAgents(): void
    {
        $role = auth()->user()->role ?? null;

        if (! in_array($role, ['admin', 'supervisor'])) {
            abort(403, 'Only admin or supervisor can manage agents.');
        }
    }

    /**
     * Tampilkan halaman Agents + data dari DB
     */
    public function index(Request $request)
    {
        $this->ensureCanManageAgents();

        $status = $request->query('status'); // all | online | offline | pending

        $baseQuery = User::agents();

        if ($status && in_array($status, ['online', 'offline', 'pending'])) {
            $baseQuery->where('status', $status);
        }

        // Tambah approved_at untuk UI
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

        // Stat untuk badge tab
        $statsQuery = User::agents();
        $counts = [
            'all'     => (clone $statsQuery)->count(),
            'online'  => (clone $statsQuery)->online()->count(),
            'offline' => (clone $statsQuery)->offline()->count(),
            'pending' => (clone $statsQuery)->pending()->count(),
        ];

        return Inertia::render('Agents/Index', [
            'agents'  => $agents,
            'counts'  => $counts,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Buat Agent Baru
     */
    public function store(Request $request)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role'  => ['required', 'in:Admin,Supervisor,Agent'],
        ]);

        $passwordPlain = Str::random(10);

        User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'role'        => strtolower($data['role']),
            'status'      => 'pending',        // Belum bisa online
            'approved_at' => null,             // Wajib approval dulu
            'is_active'   => false,
            'password'    => Hash::make($passwordPlain),
        ]);

        return redirect()->back()->with([
            'success'  => 'Agent created successfully. Approval required.',
            'newAgent' => [
                'email'    => $data['email'],
                'password' => $passwordPlain,
            ],
        ]);
    }

    /**
     * Update Settings Agent (Nama/Email/Role)
     */
    public function update(Request $request, User $user)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'role'  => ['required', 'in:Admin,Supervisor,Agent'],
        ]);

        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => strtolower($data['role']),
        ]);

        return redirect()->back()->with('success', 'Agent updated.');
    }

    /**
     * SUPER ADMIN / ADMIN / SUPERVISOR -> Approve Agent
     */
    public function approve(User $user)
    {
        $this->ensureCanManageAgents();

        $user->update([
            'approved_at' => now(),
            'status'      => 'offline',  // setelah approve, status default offline
            'is_active'   => true,
        ]);

        return back()->with('success', 'Agent approved successfully.');
    }

    /**
     * Update Online/Offline/Pending (manual via admin)
     */
    public function updateStatus(Request $request, User $user)
    {
        $this->ensureCanManageAgents();

        $data = $request->validate([
            'status' => ['required', 'in:online,offline,pending'],
        ]);

        $user->update(['status' => $data['status']]);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Delete agent
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

    /**
     * Heartbeat → Update Last Seen realtime (dipanggil dari FE).
     * Boleh dipakai semua role yang sudah di-approve.
     */
    public function heartbeat()
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->approved_at) {
                $user->update([
                    'last_seen' => now(),
                    'status'    => 'online',
                    'is_active' => true,
                ]);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Dipanggil ketika user AFK / tab lama tidak aktif.
     */
    public function forceOffline()
    {
        if (auth()->check()) {
            auth()->user()->update([
                'status'    => 'offline',
                'is_active' => false,
                'last_seen' => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
