<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;


class RegisteredUserController extends Controller
{
    /**
     * Show the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'role'     => 'required|string|in:superadmin,admin,supervisor,agent',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'status'    => 'offline',
            'is_active' => 1,
        ]);

        $user = User::create([
    'name'      => $request->name,
    'email'     => $request->email,
    'password'  => Hash::make($request->password),
    'role'      => $request->role,
    'status'    => 'offline',
    'is_active' => 1,

    // 🔥 GRACE PERIOD 24 JAM
    'email_verify_grace_until' => now()->addHours(24),
]);

$user = User::create([
    'name'      => $request->name,
    'email'     => $request->email,
    'password'  => Hash::make($request->password),
    'role'      => $request->role,
    'status'    => 'offline',
    'is_active' => 1,

    // grace + resend tracking
    'email_verify_grace_until'   => now()->addHours(24),
    'last_verification_sent_at'  => now(),
]);



        /**
         *  WAJIB untuk Email Verification Laravel Native
         * Akan otomatis mengirim email verifikasi
         * JIKA User implements MustVerifyEmail
         */
        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
