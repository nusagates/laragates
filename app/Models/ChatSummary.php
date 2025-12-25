<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSummary extends Model
{
    protected $fillable = [
        'chat_session_id',
        'summary_text',
        'created_by',
    ];
}
