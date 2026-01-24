<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_session_id',
        'customer_name',
        'customer_phone',
        'subject',
        'status',
        'priority',
        'channel',
        'assigned_to',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /* =========================
       RELATIONS
    ========================= */

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /* =========================
       LIFECYCLE LOGIC
    ========================= */

    /**
     * Trigger lifecycle when agent replies
     */
    public function markOngoingByAgent(int $agentId): void
    {
        // Ticket closed = lifecycle stop
        if ($this->status === 'closed') {
            return;
        }

        // Auto assign agent pertama
        if (! $this->assigned_to) {
            $this->assigned_to = $agentId;
        }

        // Pending → Ongoing
        if ($this->status === 'pending') {
            $this->status = 'ongoing';
        }

        // Update activity timestamp
        $this->last_message_at = now();

        $this->save();
    }

    public function auditLogs()
    {
        return $this->hasMany(TicketAuditLog::class)->latest();
    }
}
