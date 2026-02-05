<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountLockService;
use App\Services\SystemLogService;
use App\Support\IamLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    private function ensureCanManageUsers(): void
    {
        $role = auth()->user()->role ?? null;

        if (! in_array($role, ['admin', 'superadmin'])) {
            abort(403, 'Only admin or superadmin can manage users.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureCanManageUsers();

        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->whereIn('role', ['agent', 'supervisor', 'admin', 'superadmin'])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $isOnline = $user->last_seen && $user->last_seen->diffInMinutes(now()) <= 5;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $isOnline ? 'online' : 'offline',
                    'approved_at' => $user->approved_at,
                    'last_seen' => $user->last_seen,
                    'locked_until' => $user->locked_until,
                    'failed_login_attempts' => $user->failed_login_attempts,
                    'is_locked' => $user->locked_until && $user->locked_until->isFuture(),
                    'company_id' => $user->company_id,
                    'created_at' => $user->created_at,
                ];
            });

        return Inertia::render('Users/Index', [
            'users' => $users,
            'counts' => [
                'all' => $users->count(),
                'online' => $users->where('status', 'online')->count(),
                'offline' => $users->where('status', 'offline')->count(),
                'pending' => $users->whereNull('approved_at')->count(),
                'locked' => $users->where('is_locked', true)->count(),
            ],
            'filters' => $request->only(['role', 'status', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManageUsers();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:Agent,Supervisor,Admin,Superadmin',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $password = str()->random(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => strtolower($data['role']),
            'password' => Hash::make($password),
            'status' => 'pending',
            'email_verified_at' => now(),
            'company_id' => $data['company_id'] ?? auth()->user()->company_id,
        ]);

        IamLogger::log('CREATE_USER', $user->id, null, $user->only('email', 'role', 'company_id'));

        return response()->json([
            'success' => true,
            'user' => $user,
            'temp_password' => $password,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManageUsers();

        if (auth()->id() === $user->id && $request->role !== ucfirst($user->role)) {
            return response()->json(['message' => 'Cannot change your own role'], 422);
        }

        $before = $user->only(['name', 'email', 'role', 'company_id']);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:Agent,Supervisor,Admin,Superadmin',
            'company_id' => 'nullable|integer|exists:companies,id',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => strtolower($data['role']),
            'company_id' => $data['company_id'] ?? $user->company_id,
        ]);

        IamLogger::log('UPDATE_USER', $user->id, $before, $user->only(['name', 'email', 'role', 'company_id']));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'user' => $user,
            ]);
        }

        return back()->with('success', 'User updated.');
    }

    public function approve(Request $request, User $user)
    {
        $this->ensureCanManageUsers();

        $before = $user->only(['status', 'approved_at']);

        $user->update([
            'approved_at' => now(),
            'status' => 'offline',
        ]);

        IamLogger::log('APPROVE_USER', $user->id, $before, $user->only(['status', 'approved_at']));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'approved_at' => $user->approved_at,
            ]);
        }

        return back()->with('success', 'User approved.');
    }

    public function destroy(User $user)
    {
        $this->ensureCanManageUsers();

        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 422);
        }

        $before = $user->toArray();
        $user->delete();

        IamLogger::log('DELETE_USER', $user->id, $before, null);

        return response()->json(['success' => true]);
    }

    public function unlock(User $user)
    {
        $this->ensureCanManageUsers();

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

    public function resetPassword(Request $request, User $user)
    {
        $this->ensureCanManageUsers();

        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Cannot reset your own password'], 422);
        }

        $newPassword = str()->random(12);

        $before = ['has_password' => true];

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        IamLogger::log('RESET_PASSWORD', $user->id, $before, ['reset_by' => auth()->id()]);

        SystemLogService::record(
            'user_password_reset',
            'user',
            $user->id,
            null,
            null,
            ['reset_by' => auth()->id()]
        );

        return response()->json([
            'success' => true,
            'temp_password' => $newPassword,
        ]);
    }
}
