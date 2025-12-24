<?php

namespace App\Services;

use App\Models\User;

class AccountLockService
{
    const MAX_ATTEMPTS = 6;
    const LOCK_HOURS   = 24;

    public static function isLocked(User $user): bool
    {
        return $user->locked_until !== null
            && now()->lessThan($user->locked_until);
    }

    public static function recordFailedAttempt(User $user): void
    {
        $user->failed_login_attempts++;

        if ($user->failed_login_attempts >= self::MAX_ATTEMPTS) {
            $user->locked_until = now()->addHours(self::LOCK_HOURS);
            $user->failed_login_attempts = 0;
        }

        $user->save();
    }

    public static function unlock(User $user): void
    {
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();
    }
}
