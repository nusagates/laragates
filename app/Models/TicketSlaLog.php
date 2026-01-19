<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSlaLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'rule',
        'status',
        'triggered_at',
        'meta',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'meta' => 'array',
    ];
}
