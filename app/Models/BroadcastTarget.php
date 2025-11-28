<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadcast_campaign_id',
        'phone',
        'name',
        'variables',
        'status',
        'wa_message_id',
        'error_message',
        'sent_at',
        'response_log',
    ];

    protected $casts = [
        'variables' => 'array',
        'sent_at' => 'datetime',
        'response_log' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(BroadcastCampaign::class, 'broadcast_campaign_id');
    }
}
