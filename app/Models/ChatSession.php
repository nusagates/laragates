<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatSession extends Model
{
    protected $table = 'chat_sessions';

    protected $fillable = [
        'customer_id',
        'assigned_to',
        'status',              // open, pending, closed
        'is_handover',
        'bot_state',
        'bot_context',
        'pinned',
        'priority',            // vip, normal, low
        'last_agent_read_at',
        'closed_at',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'is_handover' => 'boolean',
        'last_agent_read_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /* =========================
     * RELATIONS
     * ========================= */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'chat_session_id')
            ->latestOfMany();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatSessionParticipant::class, 'chat_session_id');
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'chat_session_id');
    }

    /* =========================
     * SCOPES
     * ========================= */

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'pending']);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /* =========================
     * HELPERS
     * ========================= */

    public function markClosed(): void
    {
        $this->update([
            'status' => 'closed',
            'is_handover' => false,
            'closed_at' => now(),
        ]);

        // Sync status to linked ticket
        \App\Services\DuplicateChatToTicketService::syncStatusToTicket($this);
    }

    public function syncTicketStatus(): void
    {
        \App\Services\DuplicateChatToTicketService::syncStatusToTicket($this);
    }
}
