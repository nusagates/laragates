<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    /**
     * ==================================================
     * ALLOW ALL LOG ATTRIBUTES (SLA-AWARE)
     * ==================================================
     */
    protected $fillable = [
        'source',        // SYSTEM | SLA | IAM | etc
        'level',         // info | warning | critical
        'event',
        'description',
        'entity_type',
        'entity_id',
        'user_id',
        'user_role',
        'old_values',
        'new_values',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta'       => 'array',
    ];
}
