<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name', 'phone', 'is_blocked', 'last_message_at',
    ];

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }
}

