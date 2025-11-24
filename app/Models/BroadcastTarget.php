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
        'variables',
        'status',
        'wa_message_id',
        'error_message',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(BroadcastCampaign::class, 'broadcast_campaign_id');
    }
}
