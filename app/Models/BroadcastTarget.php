<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastTarget extends Model
{
    use HasFactory;

    protected $table = 'broadcast_targets';

    protected $fillable = [
        'broadcast_campaign_id',
        'phone',
        'variables',
        'status',
        'wa_message_id',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(BroadcastCampaign::class, 'broadcast_campaign_id');
    }
}
