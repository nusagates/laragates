<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_session_id',
        'sender',
        'user_id',
        'message',
        'type',
        'wa_message_id',
        'meta',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'meta'         => 'array',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChatSession::class, 'chat_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
}
