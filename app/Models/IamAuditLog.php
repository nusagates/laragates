<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IamAuditLog extends Model
{
    protected $fillable = [
        'actor_id',
        'subject_id',
        'action',
        'before_state',
        'after_state',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state'  => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }
}