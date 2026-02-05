<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name','monthly_price','limits'];

    protected $casts = [
        'limits' => 'array',
    ];
}
