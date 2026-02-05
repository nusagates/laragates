<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountLockService;
use App\Services\SystemLogService;

class AdminUserController extends Controller
{
    public function unlock(User $user)
    {
        if (! $user->locked_until) {
            return back()->with('info', 'User is not locked.');
        }

        AccountLockService::unlock($user);

        SystemLogService::record(
            'admin_unlock_user',
            'user',
            $user->id,
            null,
            null,
            [
                'admin_id' => auth()->id(),
            ]
        );

        return back()->with('success', 'User account unlocked.');
    }
}
