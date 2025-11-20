<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

// Default test endpoint (optional)
Route::get('/ping', function () {
    return response()->json(['message' => 'API is running']);
});

// ============= AUTH (optional sanctum) =============
// Kalau belum pakai Sanctum, sementara bisa tanpa middleware.
// Kalau sudah login via sanctum, aktifkan middleware(auth:sanctum).
// Contoh tanpa middleware dulu:

Route::get('/chats', [ChatController::class, 'index']);
Route::get('/chats/{session}', [ChatController::class, 'show']);
Route::post('/chats/{session}/send', [ChatController::class, 'send']);
Route::post('/chats/{session}/assign', [ChatController::class, 'assign']);
Route::post('/chats/{session}/close', [ChatController::class, 'close']);
