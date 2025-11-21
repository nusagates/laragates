<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;

// Default test endpoint (optional)
Route::get('/ping', function () {
    return response()->json(['message' => 'API is running']);
});

// ===== CHAT API =====
Route::get('/chats', [ChatController::class, 'index']);
Route::get('/chats/{session}', [ChatController::class, 'show']);
Route::post('/chats/{session}/send', [ChatController::class, 'send']);
Route::post('/chats/{session}/assign', [ChatController::class, 'assign']);
Route::post('/chats/{session}/close', [ChatController::class, 'close']);

// ===== BROADCAST CHANNEL =====
Broadcast::routes();
