<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'company_name',
        'timezone',
        'wa_phone',
        'wa_webhook',
        'wa_api_key',
        'notif_sound',
        'notif_desktop',
        'auto_assign_ticket'
    ];

    protected $casts = [
        'notif_sound' => 'boolean',
        'notif_desktop' => 'boolean',
        'auto_assign_ticket' => 'boolean',
    ];
}
