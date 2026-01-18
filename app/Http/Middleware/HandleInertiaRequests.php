<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $impersonating = session()->has('impersonate_original_user');
        $originalUser = null;

        if ($impersonating) {
            $originalUser = \App\Models\User::find(session()->get('impersonate_original_user'));
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'csrf_token' => csrf_token(),
            'impersonating' => $impersonating,
            'original_user' => $originalUser ? [
                'id' => $originalUser->id,
                'name' => $originalUser->name,
                'email' => $originalUser->email,
                'role' => $originalUser->role,
            ] : null,
        ];
    }
}
