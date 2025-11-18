<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::match(['get', 'post'], '/webhook', [WebhookController::class, 'handle'])
    ->name('webhook.receive');
