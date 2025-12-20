<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
    public function store(LoginRequest $request): RedirectResponse
    {
        // AUTHENTICATE (PASSWORD VALID)
        $request->authenticate();

        // SESSION SAFE
        $request->session()->regenerate();

        $user = auth()->user();

        /**
         * 🔐 IAM ENFORCEMENT
         * - User belum di-approve
         * - User belum aktif
         * - User masih pending
         */
        if (
            !$user->is_active ||
            $user->status === 'pending' ||
            $user->approved_at === null
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your account is not yet approved or has been deactivated.',
            ]);
        }

        // LOGIN SUCCESS
        return redirect()->intended(route('dashboard', absolute: false));
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
