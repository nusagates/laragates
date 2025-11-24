<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WabaWebhookController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WhatsappTemplateController;
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
        'canLogin'      => Route::has('login'),
        'canRegister'   => Route::has('register'),
        'laravelVersion'=> Application::VERSION,
        'phpVersion'    => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/chat', fn () => Inertia::render('Chat/Index'))->name('chat');

    // Agents
    Route::get('/agents', [AgentController::class, 'index'])->name('agents');
    Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
    Route::put('/agents/{user}', [AgentController::class, 'update'])->name('agents.update');
    Route::patch('/agents/{user}/status', [AgentController::class, 'updateStatus'])->name('agents.status');
    Route::delete('/agents/{user}', [AgentController::class, 'destroy'])->name('agents.destroy');

    // Tickets
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

    // Templates UI
    Route::get('/templates', fn () => Inertia::render('Templates/Index'))->name('templates');

    // Templates API
    Route::prefix('templates')->group(function () {
        Route::get('/{template}', [WhatsappTemplateController::class, 'show']);
        Route::post('/', [WhatsappTemplateController::class, 'store']);
        Route::put('/{template}', [WhatsappTemplateController::class, 'update']);
        Route::delete('/{template}', [WhatsappTemplateController::class, 'destroy']);
        Route::post('/sync', [WhatsappTemplateController::class, 'sync']);
    });

    // Broadcast
    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast');
    Route::post('/broadcast/campaigns', [BroadcastController::class, 'store'])->name('broadcast.store');

    // Settings
    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings');
});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Webhook
|--------------------------------------------------------------------------
*/
Route::get('/webhook/whatsapp', [WabaWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WabaWebhookController::class, 'receive']);

require __DIR__.'/auth.php';
