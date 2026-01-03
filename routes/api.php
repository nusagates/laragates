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
|
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
| Jangan dikasih quota karena bukan user action
|
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
|
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | WHATSAPP TEMPLATE
    |--------------------------------------------------------------------------
    */

    // Sync template (automation quota)
    Route::post('/templates/{template}/sync', [
        WhatsappTemplateController::class,
        'sync'
    ])->middleware('quota:automation,1');

    /*
    |--------------------------------------------------------------------------
    | WABA ACTION
    |--------------------------------------------------------------------------
    */

    // Send WABA main menu (message quota)
    Route::post('/waba/send-main-menu', [
        WabaMenuController::class,
        'sendMainMenu'
    ])->middleware('quota:messages,1');

    /*
    |--------------------------------------------------------------------------
    | CHAT SESSION CONTROL
    |--------------------------------------------------------------------------
    */

    // Agent take chat session
    Route::post('/chat-sessions/{session}/take', [
        ChatSessionController::class,
        'take'
    ])->middleware('quota:messages,1');

    // Agent close chat session
    Route::post('/chat-sessions/{session}/close', [
        ChatSessionController::class,
        'close'
    ])->middleware('quota:messages,1');

    /*
    |--------------------------------------------------------------------------
    | AI
    |--------------------------------------------------------------------------
    */

    // AI metrics / AI usage
    Route::get('/ai/metrics', [
        AiMetricsController::class,
        'index'
    ])->middleware('quota:ai_request,1');
});
