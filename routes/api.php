<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FonnteWebhookController;
use App\Http\Controllers\WhatsappTemplateController;
use App\Http\Controllers\WabaMenuController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/ping', fn () => response()->json(['message' => 'API is running']));

// Broadcast channels
Broadcast::routes();

// ===============================
// 🔔 WEBHOOK FONNTE (NO AUTH)
// ===============================
Route::match(['GET', 'POST'], '/webhook/fonnte', [
    FonnteWebhookController::class,
    'handle'
]);

// Simulate inbound WhatsApp (DEV ONLY)
Route::post('/simulate-inbound', [
    \App\Http\Controllers\ChatSimulationController::class,
    'simulate'
]);

// Update message status (sent / delivered / read)
Route::post('/chat-messages/{message}/status', [
    ChatController::class,
    'updateStatus'
]);

// ===============================
// 🔒 AUTHENTICATED API
// ===============================
Route::middleware('auth:sanctum')->group(function () {

    // Template sync
    Route::prefix('templates')->group(function () {
        Route::post('/{template}/sync', [
            WhatsappTemplateController::class,
            'sync'
        ]);
    });

    // ===============================
    // ⭐ WABA MENU API
    // ===============================
    Route::post('/waba/send-main-menu', [
        WabaMenuController::class,
        'sendMainMenu'
    ]);
});
