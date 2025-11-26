<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Routes (Only for Public or External API)
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/ping', fn() => response()->json(['message' => 'API is running']));

// --- NO MORE CHAT ROUTES HERE !!! ---

// Broadcast channels
Broadcast::routes();
