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
        'header',
        'body',
        'footer',
        'buttons',

        // workflow (template approval)
        'created_by',
        'approved_by',
        'approved_at',
        'workflow_notes',

        // Meta API related
        'meta_id',
        'remote_id',
        'header_type',
        'body_params_count',

        // sync logs
        'last_synced_at',
        'last_sent_at',

        // JSON metadata
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
     * Relationship to BroadcastCampaign
     * A template can be used by many campaigns
     */
    public function campaigns()
    {
        return $this->hasMany(BroadcastCampaign::class, 'whatsapp_template_id');
    }
}
