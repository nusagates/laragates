<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBehaviorLog extends Model
{
    protected $table = 'user_behavior_logs';

    protected $fillable = [
        'user_id',
        'role',
        'action',
        'endpoint',
        'method',
        'ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * 🔒 IMMUTABLE AUDIT LOG
     */
    protected static function booted()
    {
        static::updating(function () {
            abort(403, 'Behavior logs are immutable.');
        });

        static::deleting(function () {
            abort(403, 'Behavior logs are immutable.');
        });
    }
}
