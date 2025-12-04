<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FonnteWebhookController;
use App\Http\Controllers\WhatsappTemplateController;


// Health check
Route::get('/ping', fn() => response()->json(['message' => 'API is running']));

// Broadcast channels
Broadcast::routes();

// ⭐️ WEBHOOK FONNTE — WAJIB DI LUAR AUTH
Route::match(['GET', 'POST'], '/webhook/fonnte', [FonnteWebhookController::class, 'handle']);

// Simulate inbound WA
Route::post('/simulate-inbound', [\App\Http\Controllers\ChatSimulationController::class, 'simulate']);

// Update message status (sent/delivered/read)
Route::post('/chat-messages/{message}/status', [ChatController::class, 'updateStatus']);

// Protected routes (auth required)
Route::middleware('auth:sanctum')->prefix('templates')->group(function () {
    Route::post('/{template}/sync', [WhatsappTemplateController::class, 'sync']);
});
