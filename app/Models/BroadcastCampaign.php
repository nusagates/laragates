<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp_template_id',
        'audience_type',
        'total_targets',
        'status',
        'send_now',
        'send_at',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'send_now' => 'boolean',
        'send_at'  => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }

    public function targets()
    {
        return $this->hasMany(BroadcastTarget::class);
    }
}
