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
        'status',
        'pinned',
        'priority',
        'last_agent_read_at',
    ];

    protected $casts = [
        'pinned'            => 'boolean',
        'last_agent_read_at'=> 'datetime',
    ];

    /** Customer pemilik chat ini */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Agent yang menangani (User) */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** Semua pesan dalam session ini */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id');
    }

    /** Pesan terakhir (untuk preview di sidebar) */
    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'chat_session_id')
                    ->latestOfMany();
    }

    /** Peserta session (kalau multi agent) */
    public function participants(): HasMany
    {
        return $this->hasMany(ChatSessionParticipant::class, 'chat_session_id');
    }

    /** Ticket yang terhubung dengan session ini (kalau ada) */
    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class, 'chat_session_id');
    }
}
