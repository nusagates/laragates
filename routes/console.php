<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Services\TicketSlaService;
use App\Jobs\SendScheduledBroadcastJob;

/*
|--------------------------------------------------------------------------
| DEFAULT COMMAND
|--------------------------------------------------------------------------
*/
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})
->purpose('Display an inspiring quote')
->hourly();

/*
|--------------------------------------------------------------------------
| SCHEDULER — BROADCAST
|--------------------------------------------------------------------------
| Kirim broadcast yang waktunya sudah tiba
| (HARUS anti duplicate)
*/
Schedule::job(new SendScheduledBroadcastJob)
    ->everyMinute()
    ->name('broadcast-scheduler')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| SCHEDULER — TICKET SLA
|--------------------------------------------------------------------------
| Monitor SLA ticket (warning & breach)
*/
Schedule::call(function () {
    TicketSlaService::run();
})
->everyMinute()
->name('ticket-sla-check')
->withoutOverlapping();
