<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_session_id',
        'sender',
        'user_id',
        'message',
        'type',
        'wa_message_id',
        'status',
        'is_outgoing',
        'is_internal',
        'is_bot',
        'media_url',
        'media_type',
    ];

    protected $casts = [
        'is_outgoing' => 'boolean',
        'is_internal' => 'boolean',
        'is_bot'      => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class)
        ->orderBy('id', 'desc');
    }

}
