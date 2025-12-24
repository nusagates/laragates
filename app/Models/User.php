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
        'role',
        'status',
        'last_seen',
        'idle_timeout_minutes',
        'skills',
        'is_active',
        'approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen'         => 'datetime',
        'approved_at'       => 'datetime',
        'skills'            => 'array',
        'email_verified_at' => 'datetime',
        'last_seen'         => 'datetime',
        'locked_until'      => 'datetime'
    ];

    /* =======================
     | Relationships
     ======================= */
    public function sessions()
    {
        return $this->hasMany(\App\Models\ChatSession::class, 'assigned_to');
    }

    public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class, 'assigned_to');
    }

    /* =======================
     | Scopes
     ======================= */
    public function scopeAgents($query)
    {
        return $query->whereIn('role', ['agent', 'admin', 'superadmin']);
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

    // === Ambil agent yang memenuhi syarat auto-routing ===
    public function scopeAvailableAgent($query)
    {
        return $query->where([
            ['role', 'agent'],
            ['status', 'online'],
            ['is_active', true],
        ])->whereNotNull('approved_at');
    }

    /* =======================
     | Helper
     ======================= */
    public function isOnline(): bool
    {
        return $this->last_seen && $this->last_seen->gt(now()->subMinutes(3));
    }
}
