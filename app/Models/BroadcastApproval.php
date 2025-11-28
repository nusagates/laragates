<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadcast_campaign_id',
        'requested_by',
        'request_notes',
        'action',
        'acted_by',
        'action_notes',
        'acted_at',
        'snapshot',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'snapshot' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(BroadcastCampaign::class, 'broadcast_campaign_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
