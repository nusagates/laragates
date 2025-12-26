<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequestLog extends Model
{
    /**
     * Table name (explicit biar aman enterprise)
     */
    protected $table = 'ai_request_logs';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'user_id',
        'chat_session_id',
        'action',
        'model',
        'prompt_hash',
        'response_status',
        'latency_ms',
        'error_message',
        'meta',
    ];

    /**
     * Casting (penting untuk enterprise & audit)
     */
    protected $casts = [
        'meta'        => 'array',
        'latency_ms'  => 'integer',
    ];

    /**
     * ===============================
     * RELATIONSHIPS
     * ===============================
     */

    /**
     * Relasi ke User (siapa yang trigger AI)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relasi ke Chat Session (konteks WABA)
     * Ganti model kalau nama kamu beda
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChatSession::class);
    }

    /**
     * ===============================
     * QUERY SCOPES (BIAR KELIHATAN ENTERPRISE 😏)
     * ===============================
     */

    public function scopeSuccess($query)
    {
        return $query->where('response_status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('response_status', 'failed');
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }
}
