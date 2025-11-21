<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WabaWebhookController;
use App\Http\Controllers\AgentController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


/*
|--------------------------------------------------------------------------
| Public Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (UI Only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Chat Panel
    Route::get('/chat', fn () => Inertia::render('Chat/Index'))->name('chat');

    // Agents
    Route::get('/agents', [AgentController::class, 'index'])->name('agents');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::put('/agents/{user}', [AgentController::class, 'update'])->name('agents.update');
    Route::patch('/agents/{user}/status', [AgentController::class, 'updateStatus'])->name('agents.status');
    Route::delete('/agents/{user}', [AgentController::class, 'destroy'])->name('agents.destroy');

    // Tickets
    Route::get('/tickets', fn () => Inertia::render('Tickets/Index'))->name('tickets');

    // Templates
    Route::get('/templates', fn () => Inertia::render('Templates/Index'))->name('templates');

    // Broadcast
    Route::get('/broadcast', fn () => Inertia::render('Broadcast/Index'))->name('broadcast');

    // Settings
    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings');
});

/*
|--------------------------------------------------------------------------
| Profile Route
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/webhook/whatsapp', [WabaWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WabaWebhookController::class, 'receive']);


require __DIR__.'/auth.php';
