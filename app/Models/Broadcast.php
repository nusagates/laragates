<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    protected $fillable = [
        'name',
        'whatsapp_template_id',
        'audience_type',
        'audience_data',
        'csv_path',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'sent_count',
    ];

    protected $casts = [
        'audience_data' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BroadcastLog::class);
    }
}
