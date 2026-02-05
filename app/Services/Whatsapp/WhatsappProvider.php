<?php

namespace App\Services\Whatsapp;

use App\Services\Whatsapp\Drivers\FonnteDriver;
use App\Services\Whatsapp\Drivers\WazapbroDriver;

class WhatsappProvider
{
    public static function driver(): WhatsappDriverInterface
    {
        return match (config('services.whatsapp.provider')) {
            'wazapbro' => app(WazapbroDriver::class),
            default    => app(FonnteDriver::class),
        };
    }
}
