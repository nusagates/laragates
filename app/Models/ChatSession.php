<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatSession extends Model
{
    protected $fillable = [
        'customer_id',
        'agent_id',
        'status',
        'assigned_at',
        'closed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function messages(): HasMany
    {  
        return $this->hasMany(ChatMessage::class, 'chat_session_id');
    }

    public function lastMessage()
    {
        return $this->hasOne(ChatMessage::class, 'chat_session_id')->latestOfMany();
    }

    public function ticket(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
