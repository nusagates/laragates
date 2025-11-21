<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',     // Admin, Supervisor, Agent
        'status',   // online, offline, pending
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* =======================
     |  Relationships
     |======================= */

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    /* =======================
     |  Scopes
     |======================= */

    public function scopeAgents($query)
    {
        return $query->whereIn('role', ['Admin', 'Supervisor', 'Agent']);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
