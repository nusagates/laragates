<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BroadcastCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp_template_id',
        'audience_type',
        'total_targets',
        'send_now',
        'send_at',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'sent_count',
        'failed_count',
        'meta',
    ];

    protected $casts = [
        'send_now'     => 'boolean',
        'send_at'      => 'datetime',
        'approved_at'  => 'datetime',
        'meta'         => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'whatsapp_template_id');
    }

    public function targets()
    {
        return $this->hasMany(BroadcastTarget::class, 'broadcast_campaign_id');
    }

    public function approvals()
    {
        return $this->hasMany(BroadcastApproval::class, 'broadcast_campaign_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Status helpers
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function scopeForReport(Builder $q)
    {
        return $q->with('template')->withCount('targets');
    }

    // convenience accessor for summary (optional)
    public function getSummaryAttribute()
   {
      return [
         'id' => $this->id,
         'name' => $this->name,
         'template' => $this->template?->name,
         'total_targets' => $this->total_targets,
         'sent_count' => (int)$this->sent_count,
         'failed_count' => (int)$this->failed_count,
         'status' => $this->status,
         'send_at' => $this->send_at,
         'created_at' => $this->created_at,
    ];
    }

    public function isScheduled()
{
    return $this->status === 'scheduled'
        && $this->schedule_type === 'later'
        && $this->send_now == 0
        && $this->send_at <= now();
}

}
