<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'category',
        'language',
        'status',

        // Template components
        'header',
        'body',
        'footer',
        'buttons',

        // Workflow
        'created_by',
        'approved_by',
        'approved_at',
        'workflow_notes',

        // Meta API fields
        'meta_id',
        'remote_id',
        'header_type',
        'body_params_count',

        // Sync logs
        'last_synced_at',
        'last_sent_at',

        // JSON meta
        'meta',
    ];

    protected $casts = [
        'buttons'          => 'array',
        'meta'             => 'array',
        'last_synced_at'   => 'datetime',
        'last_sent_at'     => 'datetime',
        'approved_at'      => 'datetime',
    ];

    /**
     * Template -> Campaigns
     * Satu template bisa dipakai banyak campaign.
     */
    public function campaigns()
    {
        return $this->hasMany(BroadcastCampaign::class, 'whatsapp_template_id');
    }
}
