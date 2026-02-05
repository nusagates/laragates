<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Services\AccountLockService;
use App\Services\SystemLogService;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * 🔐 STEP 4 — IAM LIFECYCLE ENFORCEMENT
     */
    public function store(LoginRequest $request)
{
    $user = User::where('email', $request->email)->first();

    /**
     * BLOCK IF ACCOUNT LOCKED
     */
    if ($user && AccountLockService::isLocked($user)) {

        SystemLogService::record(
            'auth_login_blocked_account_locked',
            'user',
            $user->id,
            null,
            null,
            [
                'locked_until' => $user->locked_until,
            ]
        );

        throw ValidationException::withMessages([
            'email' => 'Account is temporarily locked. Please try again later.',
        ]);
    }

    /**
     * TRY AUTHENTICATION
     */
    try {
        $request->authenticate();
    } catch (ValidationException $e) {

        if ($user) {
            AccountLockService::recordFailedAttempt($user);

            SystemLogService::record(
                'auth_login_failed',
                'user',
                $user->id,
                null,
                null,
                [
                    'remaining_attempts' =>
                        AccountLockService::MAX_ATTEMPTS - $user->failed_login_attempts,
                ]
            );
        }

        throw $e;
    }

    /**
     * SUCCESS LOGIN
     */
    if ($user) {
        AccountLockService::unlock($user);

        SystemLogService::record(
            'auth_login_success',
            'user',
            $user->id
        );
    }

    $request->session()->regenerate();

    return redirect()->intended('/dashboard');

}
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
