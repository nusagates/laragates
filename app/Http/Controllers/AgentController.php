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
     * Tampilkan halaman Agents + data dari DB
     */
    public function index(Request $request)
    {
        $status = $request->query('status'); // all | online | offline | pending

        $baseQuery = User::agents();

        if ($status && in_array($status, ['online', 'offline', 'pending'])) {
            $baseQuery->where('status', $status);
        }

        $agents = $baseQuery
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'email'  => $user->email,
                    'role'   => $user->role,
                    'status' => $user->status,
                ];
            });

        // Untuk badge filter di atas (All, Online, Offline, Pending)
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
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    /**
     * Simpan agent baru dari form "ADD AGENT"
     */
    public function store(Request $request)
{
    $data = $request->validate([
        'name'  => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:150', 'unique:users,email'],
        'role'  => ['required', 'in:Admin,Supervisor,Agent'],
    ]);

    $passwordPlain = Str::random(10);

    User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'role'     => strtolower($data['role']),
        'status'   => 'offline',
        'password' => Hash::make($passwordPlain),
    ]);

    return redirect()->back()->with([
        'success'  => 'Agent created successfully.',
        'newAgent' => [
            'email'    => $data['email'],
            'password' => $passwordPlain,
        ]
    ]);
}

    /**
     * Update data agent (nama, email, role)
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'role'  => ['required', 'in:Admin,Supervisor,Agent'],
        ]);

        $user->update($data);

        return redirect()->back()->with('success', 'Agent updated.');
    }

    /**
     * Update status agent: online / offline / pending
     */
    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => ['required', 'in:online,offline,pending'],
        ]);

        $user->update(['status' => $data['status']]);

        return redirect()->back()->with('success', 'Status updated.');
    }

    /**
     * Hapus Agent (kecuali diri sendiri)
     */
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Agent deleted.');
    }

    public function heartbeat()
    {
       auth()->user()->update([
          'last_seen' => now(),
    ]);

    return response()->json(['status' => 'ok']);
    }

}
