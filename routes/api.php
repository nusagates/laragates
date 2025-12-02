<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;

// Health check
Route::get('/ping', fn() => response()->json(['message' => 'API is running']));

// Broadcast channels
Broadcast::routes();

// Simulate inbound WA
Route::post('/simulate-inbound', [\App\Http\Controllers\ChatSimulationController::class, 'simulate']);

// Update message status (sent/delivered/read)
Route::post('/chat-messages/{message}/status', [ChatController::class, 'updateStatus']);

Route::middleware('auth:sanctum')->prefix('templates')->group(function () {
    Route::post('/{template}/sync', [WhatsappTemplateController::class, 'sync']);
});

