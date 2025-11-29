<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WabaWebhookController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WhatsappTemplateController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\BroadcastApprovalController;
use App\Http\Controllers\BroadcastReportController;
use App\Http\Controllers\AnalyticsController;

// Chat Advanced
use App\Http\Controllers\Api\Chat\ChatSessionController;
use App\Http\Controllers\Api\Chat\ChatMessageController;
use App\Http\Controllers\Api\Chat\TypingController;


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
    | Dashboard + Chat UI
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/chat', fn() => Inertia::render('Chat/Index'))->name('chat');


    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */
    Route::get('/analytics', function () {
        return Inertia::render('Analytics/AnalyticsDashboard');
    })->name('analytics');

    Route::prefix('analytics')->group(function () {
        Route::get('/metrics', [AnalyticsController::class, 'metrics']);
        Route::get('/trends', [AnalyticsController::class, 'trends']);
        Route::get('/agents', [AnalyticsController::class, 'agents']);
        Route::get('/response-time', [AnalyticsController::class, 'responseTime']);
        Route::get('/peakhours', [AnalyticsController::class, 'peakHours']);
        Route::get('/sessions', [AnalyticsController::class, 'sessions']);
    });

    // OPTIONAL
    Route::get('/analytics/sessions', function () {
        return DB::table('sessions')->where('status', 'active')->get();
    });

    /*
    |--------------------------------------------------------------------------
    | Chat Advanced API
    |--------------------------------------------------------------------------
    */
    Route::prefix('chat')->group(function () {
        Route::get('/sessions', [ChatSessionController::class, 'index']);
        Route::get('/sessions/{session}', [ChatSessionController::class, 'show']);
        Route::post('/sessions/{session}/pin', [ChatSessionController::class, 'pin']);
        Route::post('/sessions/{session}/unpin', [ChatSessionController::class, 'unpin']);
        Route::post('/sessions/{session}/mark-read', [ChatSessionController::class, 'markRead']);

        Route::get('/sessions/{session}/messages', [ChatMessageController::class, 'index']);
        Route::post('/sessions/{session}/messages', [ChatMessageController::class, 'store']);
        Route::post('/messages/{message}/retry', [ChatMessageController::class, 'retry']);
        Route::post('/messages/{message}/mark-read', [ChatMessageController::class, 'markRead']);

        Route::post('/sessions/outbound', [ChatSessionController::class, 'outbound']);
        Route::post('/sessions/{session}/convert-ticket', [ChatSessionController::class, 'convertToTicket']);

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
    | *** FIX HERE ***
    | MAKE templates-list PUBLIC FOR BROADCAST
    |--------------------------------------------------------------------------
    */

    // 🔥 DULUNYA hanya superadmin → sekarang semua user yang login bisa akses
    Route::get('/templates-list', [WhatsappTemplateController::class, 'list'])
        ->name('templates.list');


    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:superadmin'])->group(function () {

        // Broadcast Report
        Route::get('/broadcast/report', [BroadcastReportController::class, 'index'])
            ->name('broadcast.report.index');
        Route::get('/broadcast/report/{campaign}', [BroadcastReportController::class, 'show'])
            ->name('broadcast.report.show');


        // Agent Management
        Route::get('/agents', [AgentController::class, 'index'])->name('agents');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::post('/agents/{user}/approve', [AgentController::class, 'approve'])->name('agents.approve');
        Route::put('/agents/{user}', [AgentController::class, 'update'])->name('agents.update');
        Route::patch('/agents/{user}/status', [AgentController::class, 'updateStatus'])->name('agents.status');
        Route::delete('/agents/{user}', [AgentController::class, 'destroy'])->name('agents.destroy');

        Route::post('/templates/{template}/send', [WhatsappTemplateController::class, 'send'])
    ->name('templates.send');


        /*
        |--------------------------------------------------------------------------
        | Broadcast Approvals
        |--------------------------------------------------------------------------
        */
        Route::get('/broadcast/approvals', [BroadcastApprovalController::class, 'index'])
            ->name('broadcast.approvals.index');

        Route::post('/broadcast/approvals/{approval}/approve',
            [BroadcastApprovalController::class, 'approve'])
            ->name('broadcast.approvals.approve');

        Route::post('/broadcast/approvals/{approval}/reject',
            [BroadcastApprovalController::class, 'reject'])
            ->name('broadcast.approvals.reject');


        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        */
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/general', [SettingController::class, 'saveGeneral'])->name('settings.general');
        Route::post('/settings/waba', [SettingController::class, 'saveWaba'])->name('settings.waba');
        Route::post('/settings/preferences', [SettingController::class, 'savePreferences'])->name('settings.preferences');
        Route::get('/settings/test-webhook', [SettingController::class, 'testWebhook'])->name('settings.testWebhook');


        /*
        |--------------------------------------------------------------------------
        | TEMPLATE MODULE
        |--------------------------------------------------------------------------
        */
        Route::get('/templates', [WhatsappTemplateController::class, 'index'])->name('templates.index');

        Route::prefix('templates')->group(function () {

            Route::post('/', [WhatsappTemplateController::class, 'store'])->name('templates.store');
            Route::get('/{template}', [WhatsappTemplateController::class, 'show'])->name('templates.show');
            Route::put('/{template}', [WhatsappTemplateController::class, 'update'])->name('templates.update');
            Route::delete('/{template}', [WhatsappTemplateController::class, 'destroy'])->name('templates.destroy');

            Route::post('/{template}/submit', [WhatsappTemplateController::class, 'submit'])->name('templates.submit');
            Route::post('/{template}/approve', [WhatsappTemplateController::class, 'approve'])->name('templates.approve');
            Route::post('/{template}/reject', [WhatsappTemplateController::class, 'reject'])->name('templates.reject');

            Route::post('/{template}/sync', [WhatsappTemplateController::class, 'sync'])
                ->name('templates.sync');

            Route::post('/{template}/versions', [WhatsappTemplateController::class, 'createVersion'])
                ->name('templates.versions.create');
            Route::post('/{template}/versions/{version}/revert', [WhatsappTemplateController::class, 'revertVersion'])
                ->name('templates.versions.revert');

            Route::post('/{template}/notes', [WhatsappTemplateController::class, 'addNote'])
                ->name('templates.notes.add');
                // routes/web.php
Route::post('/templates/{template}/send', [WhatsappTemplateController::class, 'sendMessage']);

        });

        Route::post('/templates-sync-all', [WhatsappTemplateController::class, 'syncAll'])
            ->name('templates.sync-all'); 

            Route::post('/templates/{template}/send-preview',
    [WhatsappTemplateController::class, 'sendPreview']
)->name('templates.send-preview');



        /*
        |--------------------------------------------------------------------------
        | BROADCAST MODULE
        |--------------------------------------------------------------------------
        */
        Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast');
        Route::post('/broadcast/campaigns', [BroadcastController::class, 'store'])->name('broadcast.store');
        Route::post('/broadcast/campaigns/{campaign}/upload-targets',
            [BroadcastController::class, 'uploadTargets'])
            ->name('broadcast.upload-targets');

        Route::post('/broadcast/{campaign}/request-approval',
            [BroadcastApprovalController::class, 'requestApproval'])
            ->name('broadcast.request-approval');

        Route::post('/broadcast/{campaign}/approve',
            [BroadcastApprovalController::class, 'approve'])
            ->name('broadcast.approve');

        Route::post('/broadcast/{campaign}/reject',
            [BroadcastApprovalController::class, 'reject'])
            ->name('broadcast.reject');
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
| WhatsApp Webhook
|--------------------------------------------------------------------------
*/
Route::get('/webhook/whatsapp', [WabaWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WabaWebhookController::class, 'receive']);

require __DIR__.'/auth.php';
