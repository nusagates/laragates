<?php

namespace App\Support;

use App\Models\IamAuditLog;

class IamLogger
{
    public static function log(
        string $action,
        int $subjectId,
        array|null $before = null,
        array|null $after = null
    ): void {
        IamAuditLog::create([
            'actor_id'    => auth()->id(),
            'subject_id'  => $subjectId,
            'action'      => $action,
            'before_state'=> $before,
            'after_state' => $after,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}