<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Authenticatable
{
    protected $table = 'agents';

    protected $fillable = [
        'name',
        'email',
        'password',
        // tambahkan field lain yang kamu punya
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'assigned_to');
    }

    public function participatingSessions(): HasMany
    {
        return $this->hasMany(ChatSessionParticipant::class, 'agent_id');
    }
}
