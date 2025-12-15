<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SendScheduledBroadcastJob;

// Command default
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// === SCHEDULER BROADCAST ===
// Check dan kirim broadcast yang waktunya telah tiba
Schedule::job(new SendScheduledBroadcastJob)->everyMinute();
