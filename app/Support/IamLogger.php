<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IamLogger
{
    public static function log(
        string $action,
        int|string|null $targetUserId,
        array|null $before = null,
        array|null $after = null
    ): void {
        DB::table('iam_logs')->insert([
            'action'        => $action,
            'actor_id'      => Auth::id(),
            'target_user_id'=> $targetUserId,
            'before'        => $before ? json_encode($before) : null,
            'after'         => $after ? json_encode($after) : null,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'created_at'    => now(),
        ]);
    }
}
