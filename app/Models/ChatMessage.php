<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_session_id',

        // sender info
        'sender',          // customer | agent | system
        'user_id',         // agent id (null jika customer/system)

        // content
        'message',
        'type',

        // whatsapp
        'wa_message_id',

        // delivery & status
        'status',          // pending | sent | delivered | read | failed
        'delivery_status', // queued | sending | sent | delivered | read | failed | failed_final
        'state_id',
        'retry_count',
        'last_retry_at',
        'last_error',

        // flags
        'is_outgoing',
        'is_internal',
        'is_bot',

        // media
        'media_url',
        'media_type',
        'file_name',
        'file_size',
        'mime_type',

        // reactions
        'reactions',
    ];

    protected $casts = [
        'is_outgoing' => 'boolean',
        'is_internal' => 'boolean',
        'is_bot' => 'boolean',
        'last_retry_at' => 'datetime',
        'reactions' => 'array',
    ];

    /**
     * =========================
     * RELATIONS
     * =========================
     */

    /** Relasi ke session chat */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    /** Agent pengirim pesan (jika sender = agent) */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * ⚠️ Customer TIDAK direlasikan langsung ke chat_messages
     * Customer diambil via:
     * $message->session->customer
     */
}
