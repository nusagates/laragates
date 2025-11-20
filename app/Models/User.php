<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',        // admin / supervisor / agent
        'status',      // online / offline / pending
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** 
     * Chat sessions yang ditangani agent ini 
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'agent_id');
    }

    /**
     * Tickets yang dikerjakan oleh agent ini
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }
}
