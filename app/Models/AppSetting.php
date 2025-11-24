<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'company_name', 'timezone',
        'wa_phone', 'wa_webhook', 'wa_api_key',
        'notif_sound', 'notif_desktop', 'auto_assign_ticket'
    ];
}
