<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
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
        'locked_until'      => 'datetime',
        'skills'            => 'array',
    ];

    /* =======================
     | Relationships
     ======================= */

    public function sessions()
    {
        return $this->hasMany(\App\Models\ChatSession::class, 'assigned_to');
    }

    public function chatSessions()
    {
        return $this->hasMany(
            \App\Models\ChatSession::class,
            'assigned_to'
        );
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
        return $this->last_seen
            && $this->last_seen->gt(now()->subMinutes(3));
    }

    public function isInEmailVerificationGracePeriod(): bool
{
    return
        !$this->hasVerifiedEmail()
        && $this->email_verify_grace_until
        && now()->lessThan($this->email_verify_grace_until);
}

public function canAutoResendVerification(): bool
{
    if ($this->hasVerifiedEmail()) {
        return false;
    }

    if (!$this->email_verify_grace_until || now()->greaterThan($this->email_verify_grace_until)) {
        return false;
    }

    if ($this->verification_resend_count >= 2) {
        return false;
    }

    if (!$this->last_verification_sent_at) {
        return true;
    }

    // resend minimal tiap 6 jam
    return $this->last_verification_sent_at->diffInHours(now()) >= 6;
}


}
