<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\FonnteWebhookController;
use App\Http\Controllers\WhatsappTemplateController;
use App\Http\Controllers\WabaMenuController;
use App\Http\Controllers\AiMetricsController;
use App\Http\Controllers\ChatSimulationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua endpoint API aplikasi (WABA + CRM)
| Quota hanya dipasang pada user-triggered action
*/

/*
|--------------------------------------------------------------------------
| HEALTH CHECK
|--------------------------------------------------------------------------
*/
Route::get('/ping', fn () => response()->json([
    'message' => 'API is running'
]));

/*
|--------------------------------------------------------------------------
| BROADCAST CHANNELS
|--------------------------------------------------------------------------
*/
Broadcast::routes();

/*
|--------------------------------------------------------------------------
| WEBHOOK & SYSTEM ROUTES (NO AUTH, NO QUOTA)
|--------------------------------------------------------------------------
*/

// 🔔 Webhook Fonnte
Route::match(['GET', 'POST'], '/webhook/fonnte', [
    FonnteWebhookController::class,
    'handle'
]);

// 🧪 Simulate inbound WhatsApp (DEV ONLY)
Route::post('/simulate-inbound', [
    ChatSimulationController::class,
    'simulate'
]);

// 📦 Update WA delivery status
Route::post('/chat-messages/{message}/status', [
    ChatController::class,
    'updateStatus'
]);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED API (USER ACTION)
|--------------------------------------------------------------------------
| Semua di bawah ini:
| - auth:sanctum
| - boleh kena quota
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | VERIFIED OR GRACE PERIOD USERS (ANTI ABUSE)
    |--------------------------------------------------------------------------
    | - verified ✅
    | - belum verified tapi masih grace period ✅
    | - grace habis → 403 ❌
    */
    Route::middleware('verified_or_grace')->group(function () {

        /*
        |-------------------------------
        | WHATSAPP TEMPLATE
        |-------------------------------
        */
        Route::post('/templates/{template}/sync', [
            WhatsappTemplateController::class,
            'sync'
        ])->middleware('quota:automation,1');

        /*
        |-------------------------------
        | WABA ACTION
        |-------------------------------
        */
        Route::post('/waba/send-main-menu', [
            WabaMenuController::class,
            'sendMainMenu'
        ])->middleware('quota:messages,1');

        /*
        |-------------------------------
        | AI
        |-------------------------------
        */
        Route::get('/ai/metrics', [
            AiMetricsController::class,
            'index'
        ])->middleware('quota:ai_request,1');
    });

    /*
    |--------------------------------------------------------------------------
    | SAFE ROUTES (BOLEH UNTUK UNVERIFIED USER)
    |--------------------------------------------------------------------------
    | Tidak berisiko spam / abuse
    */

    /*
    |-------------------------------
    | CHAT SESSION CONTROL
    |-------------------------------
    */
    Route::post('/chat-sessions/{session}/take', [
        ChatSessionController::class,
        'take'
    ])->middleware('quota:messages,1');

    Route::post('/chat-sessions/{session}/close', [
        ChatSessionController::class,
        'close'
    ])->middleware('quota:messages,1');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // seluruh API user action yang harus diblokir jika suspended
});
});
