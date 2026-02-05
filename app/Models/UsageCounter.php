<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageCounter extends Model
{
    protected $fillable = [
        'company_id',
        'key',
        'used',
        'period_start',
        'period_end'
    ];
}
