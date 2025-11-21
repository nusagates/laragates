<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'name',
        'category',
        'language',
        'status',
        'structure',
    ];

    protected $casts = [
        'structure' => 'array',
    ];
}

