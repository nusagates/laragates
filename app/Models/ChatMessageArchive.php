<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageArchive extends Model
{
    use HasFactory;

    protected $table = 'chat_messages_archive';

    protected $fillable = [
        'chat_session_archive_id',
        'customer_id',
        'sender',
        'message',
        'type',
        'media_url',
        'media_type',
        'delivery_status',
        'is_outgoing',
        'is_internal',
        'reactions',
    ];

    protected function casts(): array
    {
        return [
            'is_outgoing' => 'boolean',
            'is_internal' => 'boolean',
            'reactions' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSessionArchive::class, 'chat_session_archive_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
