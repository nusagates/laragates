<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\BroadcastApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WabaWebhookController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WhatsappTemplateController;

// Chat Advanced
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
| Protected Routes (Authenticated & Verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Chat UI (Inertia)
    |--------------------------------------------------------------------------
    */
    Route::get('/chat', fn () => Inertia::render('Chat/Index'))
        ->name('chat');

    /*
    |--------------------------------------------------------------------------
    | Chat Advanced API
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

        // Outbound & Ticket
        Route::post('/sessions/outbound', [ChatSessionController::class, 'outbound']);
        Route::post('/sessions/{session}/convert-ticket', [ChatSessionController::class, 'convertToTicket']);

        // Typing
        Route::post('/sessions/{session}/typing', [TypingController::class, 'typing']);
    });

    /*
    |--------------------------------------------------------------------------
    | Ticketing System
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN ONLY
    |--------------------------------------------------------------------------
    */
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
        | Settings Page (FIXED — sebelumnya menyebabkan 404)
        |--------------------------------------------------------------------------
        */
        Route::get('/settings', function () {
            return Inertia::render('Settings/Index');
        })->name('settings');

        /*
        |--------------------------------------------------------------------------
        | TEMPLATE MODULE (Management + Workflow + Sync)
        |--------------------------------------------------------------------------
        */
        Route::get('/templates', [WhatsappTemplateController::class, 'index'])
            ->name('templates.index');

        Route::get('/templates-list', [WhatsappTemplateController::class, 'list'])
            ->name('templates.list');

        Route::prefix('templates')->group(function () {

            // CRUD
            Route::post('/', [WhatsappTemplateController::class, 'store'])->name('templates.store');
            Route::get('/{template}', [WhatsappTemplateController::class, 'show'])->name('templates.show');
            Route::put('/{template}', [WhatsappTemplateController::class, 'update'])->name('templates.update');
            Route::delete('/{template}', [WhatsappTemplateController::class, 'destroy'])->name('templates.destroy');

            // Workflow
            Route::post('/{template}/submit', [WhatsappTemplateController::class, 'submit'])->name('templates.submit');
            Route::post('/{template}/approve', [WhatsappTemplateController::class, 'approve'])->name('templates.approve');
            Route::post('/{template}/reject', [WhatsappTemplateController::class, 'reject'])->name('templates.reject');

            // Sync From Meta (single)
            Route::post('/{template}/sync', [WhatsappTemplateController::class, 'syncSingle'])
                ->name('templates.sync');

            // Send Template
            Route::post('/{template}/send', [WhatsappTemplateController::class, 'send'])
                ->name('templates.send');

            // Versions
            Route::post('/{template}/versions', [WhatsappTemplateController::class, 'createVersion'])->name('templates.versions.create');
            Route::post('/{template}/versions/{version}/revert', [WhatsappTemplateController::class, 'revertVersion'])->name('templates.versions.revert');

            // Notes
            Route::post('/{template}/notes', [WhatsappTemplateController::class, 'addNote'])->name('templates.notes.add');
        });

        /*
        |--------------------------------------------------------------------------
        | BROADCAST MODULE (Main Features + Workflow Approval)
        |--------------------------------------------------------------------------
        */

        // MAIN PAGE
        Route::get('/broadcast', [BroadcastController::class, 'index'])
            ->name('broadcast');

        // Create Campaign
        Route::post('/broadcast/campaigns', [BroadcastController::class, 'store'])
            ->name('broadcast.store');

        // Upload CSV/Excel
        Route::post('/broadcast/campaigns/{campaign}/upload-targets',
            [BroadcastController::class, 'uploadTargets']
        )->name('broadcast.upload-targets');

        // Request Approval (Agent)
        Route::post('/broadcast/{campaign}/request-approval',
            [BroadcastApprovalController::class, 'requestApproval']
        )->name('broadcast.request-approval');

        // Approve (Admin)
        Route::post('/broadcast/{campaign}/approve',
            [BroadcastApprovalController::class, 'approve']
        )->name('broadcast.approve');

        // Reject (Admin)
        Route::post('/broadcast/{campaign}/reject',
            [BroadcastApprovalController::class, 'reject']
        )->name('broadcast.reject');
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
