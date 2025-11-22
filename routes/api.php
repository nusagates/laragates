<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\ChatController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Optional: test endpoint
Route::get('/ping', function () {
    return response()->json(['message' => 'API is running']);
});

/*
|--------------------------------------------------------------------------
| CHAT API
|--------------------------------------------------------------------------
*/
Route::get('/chats', [ChatController::class, 'index']);
Route::get('/chats/{session}', [ChatController::class, 'show']);
Route::post('/chats/{session}/send', [ChatController::class, 'send']);
Route::post('/chats/{session}/assign', [ChatController::class, 'assign']);
Route::post('/chats/{session}/close', [ChatController::class, 'close']);

// NEW: outbound chat (CS kirim duluan ke nomor baru / existing)
Route::post('/chats/outbound', [ChatController::class, 'outbound']);

/*
|--------------------------------------------------------------------------
| BROADCAST CHANNELS
|--------------------------------------------------------------------------
*/
Broadcast::routes();
