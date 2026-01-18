<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSessionArchive extends Model
{
    use HasFactory;

    protected $table = 'chat_sessions_archive';

    protected $fillable = [
        'customer_id',
        'assigned_to',
        'pinned',
        'priority',
        'status',
        'is_handover',
        'bot_state',
        'bot_context',
        'closed_at',
        'archived_at',
        'last_agent_read_at',
        'first_response_at',
        'first_response_seconds',
        'resolution_seconds',
        'sla_status',
    ];

    protected function casts(): array
    {
        return [
            'is_handover' => 'boolean',
            'pinned' => 'boolean',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
            'last_agent_read_at' => 'datetime',
            'first_response_at' => 'datetime',
        ];
    }

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
        return $this->hasMany(ChatMessageArchive::class, 'chat_session_archive_id');
    }
}
