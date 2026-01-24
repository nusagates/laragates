<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadcastTarget extends Model
{
    protected $table = 'broadcast_targets';

    protected $fillable = [
        'broadcast_campaign_id',
        'phone',
        'name',
        'variables',
        'status',
        'sent_at',
        'error_message',
        'attempts',
        'wa_message_id',
        'state_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(BroadcastCampaign::class, 'broadcast_campaign_id');
    }

    // convenience helpers
    public function markSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markFailed($error = null)
    {
        $this->increment('attempts');
        $this->update([
            'status' => 'failed',
            'error_message' => $error ? (string) $error : null,
        ]);
    }
}
