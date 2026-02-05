<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\IamLogger;
use Illuminate\Http\Request;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a user
     */
    public function impersonate(Request $request, User $user)
    {
        $currentUser = auth()->user();

        // Check if already impersonating
        if (session()->has('impersonate_original_user')) {
            return response()->json([
                'message' => 'You are already impersonating another user. Please leave impersonation first.',
            ], 403);
        }

        // Authorization check
        if (! $this->canImpersonate($currentUser, $user)) {
            return response()->json([
                'message' => 'You are not authorized to impersonate this user.',
            ], 403);
        }

        // Store original user ID in session
        session()->put('impersonate_original_user', $currentUser->id);

        // Log the impersonation action
        IamLogger::log(
            'IMPERSONATE_START',
            $user->id,
            null,
            [
                'impersonator_id' => $currentUser->id,
                'impersonator_name' => $currentUser->name,
                'impersonator_role' => $currentUser->role,
                'target_name' => $user->name,
                'target_role' => $user->role,
            ]
        );

        // Switch to the target user
        auth()->login($user);

        return response()->json([
            'success' => true,
            'message' => "Now impersonating {$user->name}",
            'redirect' => '/dashboard',
        ]);
    }

    /**
     * Stop impersonating and return to original user
     */
    public function leave(Request $request)
    {
        $originalUserId = session()->get('impersonate_original_user');

        if (! $originalUserId) {
            return response()->json([
                'message' => 'You are not impersonating anyone.',
            ], 400);
        }

        $currentUser = auth()->user();
        $originalUser = User::find($originalUserId);

        if (! $originalUser) {
            session()->forget('impersonate_original_user');

            return response()->json([
                'message' => 'Original user not found.',
            ], 404);
        }

        // Log leaving impersonation
        IamLogger::log(
            'IMPERSONATE_LEAVE',
            $currentUser->id,
            null,
            [
                'impersonator_id' => $originalUser->id,
                'impersonator_name' => $originalUser->name,
                'impersonated_name' => $currentUser->name,
                'impersonated_role' => $currentUser->role,
            ]
        );

        // Remove impersonation session
        session()->forget('impersonate_original_user');

        // Switch back to original user
        auth()->login($originalUser);

        return response()->json([
            'success' => true,
            'message' => 'Returned to your account',
            'redirect' => '/dashboard',
        ]);
    }

    /**
     * Check if the current user can impersonate the target user
     */
    private function canImpersonate(User $impersonator, User $target): bool
    {
        // Cannot impersonate yourself
        if ($impersonator->id === $target->id) {
            return false;
        }

        // Superadmin can impersonate anyone (agent, supervisor, admin)
        if ($impersonator->role === 'superadmin') {
            return in_array($target->role, ['agent', 'supervisor', 'admin']);
        }

        // Admin can only impersonate agent and supervisor
        if ($impersonator->role === 'admin') {
            return in_array($target->role, ['agent', 'supervisor']);
        }

        // No other roles can impersonate
        return false;
    }
}
