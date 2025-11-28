<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WabaWebhookController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WhatsappTemplateController;

// Chat Advanced Controllers
use App\Http\Controllers\Api\Chat\ChatSessionController;
use App\Http\Controllers\Api\Chat\ChatMessageController;
use App\Http\Controllers\Api\Chat\TypingController;

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

/*
|--------------------------------------------------------------------------
| Authenticated Panel Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // =======================
    //   ACCESS: ALL ROLES
    // =======================

    // Dashboard
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Chat UI
    Route::get('/chat', fn () => Inertia::render('Chat/Index'))->name('chat');

    /*
    |--------------------------------------------------------------------------
    | Chat Advanced (sessions + messages + typing)
    |--------------------------------------------------------------------------
    */
    Route::prefix('chat')->group(function () {

        // Sessions
        Route::get('/sessions', [ChatSessionController::class, 'index']);
        Route::get('/sessions/{session}', [ChatSessionController::class, 'show']);
        Route::post('/sessions/{session}/pin', [ChatSessionController::class, 'pin']);
        Route::post('/sessions/{session}/unpin', [ChatSessionController::class, 'unpin']);
        Route::post('/sessions/{session}/mark-read', [ChatSessionController::class, 'markRead']);

        // Messages
        Route::get('/sessions/{session}/messages', [ChatMessageController::class, 'index']);
        Route::post('/sessions/{session}/messages', [ChatMessageController::class, 'store']);
        Route::post('/messages/{message}/retry', [ChatMessageController::class, 'retry']);
        Route::post('/messages/{message}/mark-read', [ChatMessageController::class, 'markRead']);

        // Outbound (Start Chat)
        Route::post('/sessions/outbound', [ChatSessionController::class, 'outbound']);

        // Convert to Ticket
        Route::post('/sessions/{session}/convert-ticket', [ChatSessionController::class, 'convertToTicket']);

        // Typing Indicator
        Route::post('/sessions/{session}/typing', [TypingController::class, 'typing']);
    });


    /*
    |--------------------------------------------------------------------------
    | Ticket System (accessible by agent + superadmin)
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');


    // ============================
    //   ACCESS: SUPERADMIN ONLY
    // ============================

    Route::middleware(['role:superadmin'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Agent Management
        |--------------------------------------------------------------------------
        */
        Route::get('/agents', [AgentController::class, 'index'])->name('agents');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::post('/agents/{user}/approve', [AgentController::class, 'approve'])->name('agents.approve');
        Route::put('/agents/{user}', [AgentController::class, 'update'])->name('agents.update');
        Route::patch('/agents/{user}/status', [AgentController::class, 'updateStatus'])->name('agents.status');
        Route::delete('/agents/{user}', [AgentController::class, 'destroy'])->name('agents.destroy');

        /*
        |--------------------------------------------------------------------------
        | Templates
        |--------------------------------------------------------------------------
        */
        Route::get('/templates', fn () => Inertia::render('Templates/Index'))->name('templates');

        Route::prefix('templates')->group(function () {
            Route::get('/{template}', [WhatsappTemplateController::class, 'show']);
            Route::post('/', [WhatsappTemplateController::class, 'store']);
            Route::put('/{template}', [WhatsappTemplateController::class, 'update']);
            Route::delete('/{template}', [WhatsappTemplateController::class, 'destroy']);
            Route::post('/sync', [WhatsappTemplateController::class, 'sync']);
        });

        /*
        |--------------------------------------------------------------------------
        | Broadcast
        |--------------------------------------------------------------------------
        */
        Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast');
        Route::post('/broadcast/campaigns', [BroadcastController::class, 'store'])->name('broadcast.store');

        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        */
        Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings');

    });
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
| Webhook WA
|--------------------------------------------------------------------------
*/
Route::get('/webhook/whatsapp', [WabaWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WabaWebhookController::class, 'receive']);

require __DIR__.'/auth.php';
