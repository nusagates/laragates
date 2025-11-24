<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'name', 'category', 'language', 'status',
        'header', 'body', 'footer', 'buttons',
        'meta_id', 'last_synced_at'
    ];

    protected $casts = [
        'buttons' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
