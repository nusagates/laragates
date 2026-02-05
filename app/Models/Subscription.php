<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'company_id',
        'plan_id',
        'start_at',
        'end_at',
        'status'
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
