<?php

use App\Http\Controllers\Admin\AiReportController;
use App\Http\Controllers\Admin\AiSettingController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AiSummaryController;
// Controllers
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Api\Chat\ChatMessageController;
use App\Http\Controllers\Api\Chat\ChatSessionController;
use App\Http\Controllers\Api\CustomerSummaryController;
use App\Http\Controllers\BroadcastApprovalController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\BroadcastReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Dashboard\TakeChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\ProfileController; // ✅ CONTACT CONTROLLER
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TestBroadcastController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaMenuController;
use App\Http\Middleware\IdleTimeout;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Template;
// Middleware
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
// Models
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
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
| PUBLIC PAGES
|--------------------------------------------------------------------------
*/
Route::get('/pricing', fn () => Inertia::render('Pricing'))->name('pricing');
Route::get('/solutions', fn () => Inertia::render('Solutions'))->name('solutions');
Route::get('/docs', fn () => Inertia::render('Docs'))->name('docs');

/*
|--------------------------------------------------------------------------
| EXTRA ROUTE
|--------------------------------------------------------------------------
*/
Route::get('/templates-list', fn () => Template::orderBy('id', 'desc')->get());

/*
|--------------------------------------------------------------------------
| AUTH PROTECTED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', IdleTimeout::class])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | CONTACTS (CRM) ✅ ADDED
    |--------------------------------------------------------------------------
    */
    Route::get('/contacts-ui', fn () => Inertia::render('Contacts/Index'))
        ->name('contacts.ui');

    Route::middleware(['auth'])
        ->get('/contacts/{customer}/timeline', [
            \App\Http\Controllers\ContactTimelineController::class,
            'index',
        ])
        ->name('contacts.timeline');

    Route::middleware(['auth'])->group(function () {
        Route::get('/customers/{id}/summary', [
            CustomerSummaryController::class,
            'show',
        ]);
    });

    Route::prefix('contacts')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/{customer}', [ContactController::class, 'show'])->name('contacts.show');
        Route::put('/{customer}', [ContactController::class, 'update'])->name('contacts.update');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth'])
        ->name('dashboard');

    Route::middleware(['auth'])->group(function () {
        Route::post(
            '/dashboard/take-chat/{session}',
            [TakeChatController::class, 'take']
        )->name('dashboard.take-chat');
    });

    Route::get('/chat', fn () => Inertia::render('Chat/Index'))->name('chat');

    /*
    |--------------------------------------------------------------------------
    | TEST BROADCAST (DEBUG ONLY)
    |--------------------------------------------------------------------------
    */
    Route::get('/test/broadcast', [TestBroadcastController::class, 'index'])
        ->name('test.broadcast.index');
    Route::post('/test/broadcast/trigger', [TestBroadcastController::class, 'trigger'])
        ->name('test.broadcast.trigger');

    /*
    |--------------------------------------------------------------------------
    | AGENT HEARTBEAT
    |--------------------------------------------------------------------------
    */
    Route::post('/agent/heartbeat', \App\Http\Controllers\Api\AgentHeartbeatController::class)
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

    /*
    |--------------------------------------------------------------------------
    | ANALYTICS
    |--------------------------------------------------------------------------
    */
    Route::get('/analytics', fn () => Inertia::render('Analytics/AnalyticsDashboard'))
        ->name('analytics');

    Route::prefix('analytics')->group(function () {
        Route::get('/metrics', [AnalyticsController::class, 'metrics']);
        Route::get('/trends', [AnalyticsController::class, 'trends']);
        Route::get('/agents', [AnalyticsController::class, 'agents']);
        Route::get('/response-time', [AnalyticsController::class, 'responseTime']);
        Route::get('/peakhours', [AnalyticsController::class, 'peakHours']);
        Route::get('/sessions', [AnalyticsController::class, 'sessions']);
    });

    /*
    |--------------------------------------------------------------------------
    | CHAT
    |--------------------------------------------------------------------------
    */
    Route::prefix('chat')->group(function () {
        Route::get('/sessions', [ChatSessionController::class, 'index']);
        Route::get('/sessions/{session}', [ChatSessionController::class, 'show']);
        Route::post('/sessions/{session}/pin', [ChatSessionController::class, 'pin']);
        Route::post('/sessions/{session}/unpin', [ChatSessionController::class, 'unpin']);
        Route::post('/sessions/{session}/mark-read', [ChatSessionController::class, 'markRead']);
        Route::post('/sessions/{session}/close', [ChatSessionController::class, 'close']);
        Route::get('/agents/available', [ChatSessionController::class, 'getAvailableAgents']);
        Route::post('/sessions/{session}/reassign', [ChatSessionController::class, 'reassign']);
        Route::get('/sessions/{session}/messages', [ChatMessageController::class, 'index']);
        Route::post('/sessions/{session}/messages', [ChatMessageController::class, 'store']);
        Route::post('/messages/{message}/retry', [ChatMessageController::class, 'retry']);
        Route::post('/messages/{message}/mark-read', [ChatMessageController::class, 'markRead']);
        Route::post('/messages/{message}/reaction', [ChatMessageController::class, 'addReaction']);
        Route::post('/sessions/outbound', [ChatController::class, 'outbound'])->name('chat.outbound');
    });

    /*
    |--------------------------------------------------------------------------
    | AI SUMMARY
    |--------------------------------------------------------------------------
    */
    Route::post('/ai/chat-summary', [AiSummaryController::class, 'generate'])
        ->name('ai.chat.summary')
        ->can('use-ai-summary');

    Route::middleware(['auth:sanctum'])
        ->prefix('admin/ai')
        ->group(function () {
            Route::get('/settings', [AiSettingController::class, 'show']);
            Route::put('/settings', [AiSettingController::class, 'update']);
        });

    /*
    |--------------------------------------------------------------------------
    | TICKETS
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');

    /*
    |--------------------------------------------------------------------------
    | TEMPLATES
    |--------------------------------------------------------------------------
    */
    Route::prefix('templates')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('templates.index');
        Route::post('/', [TemplateController::class, 'store'])->name('templates.store');
        Route::get('/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::put('/{template}', [TemplateController::class, 'update'])->name('templates.update');
        Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/{template}/submit', [TemplateController::class, 'submitForApproval'])->name('templates.submit');
        Route::post('/{template}/approve', [TemplateController::class, 'approve'])->name('templates.approve');
        Route::post('/{template}/reject', [TemplateController::class, 'reject'])->name('templates.reject');
        Route::post('/{template}/sync', [TemplateController::class, 'sync'])->name('templates.sync');
    });

    /*
    |--------------------------------------------------------------------------
    | BROADCAST
    |--------------------------------------------------------------------------
    */
    Route::middleware([RoleMiddleware::class.':superadmin,supervisor'])->group(function () {
        Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast');
        Route::post('/broadcast/campaigns', [BroadcastController::class, 'store'])->name('broadcast.store');
        Route::post(
            '/broadcast/campaigns/{campaign}/upload-targets',
            [BroadcastController::class, 'uploadTargets']
        )->name('broadcast.upload-targets');
        Route::post(
            '/broadcast/campaigns/{campaign}/request-approval',
            [BroadcastApprovalController::class, 'requestApproval']
        )->name('broadcast.request-approval');
        Route::get('/broadcast/download-sample-csv', [BroadcastController::class, 'downloadSampleCsv'])->name('broadcast.download-sample-csv');
        Route::post('/broadcast/targets/{target}/retry', [BroadcastReportController::class, 'retryTarget'])->name('broadcast.target.retry');
        Route::get('/broadcast/reports', [BroadcastReportController::class, 'index'])->name('broadcast.reports');
        Route::get(
            '/broadcast/reports/{campaign}',
            [BroadcastReportController::class, 'show']
        )->name('broadcast.report.show');
    });

    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware([RoleMiddleware::class.':superadmin'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/save', [SettingController::class, 'save'])->name('settings.save');
        Route::post('/settings/general', [SettingController::class, 'saveGeneral'])->name('settings.general');
        Route::post('/settings/waba', [SettingController::class, 'saveWaba'])->name('settings.waba');
        Route::post('/settings/preferences', [SettingController::class, 'savePreferences'])->name('settings.preferences');
        Route::post('/settings/test-webhook', [SettingController::class, 'testWebhook'])->name('settings.test-webhook');

        Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system.logs');
        Route::get('/system-logs/export', [SystemLogController::class, 'export'])->name('system.logs.export');
        Route::get('/system-logs/sources', [SystemLogController::class, 'sources']);
    });

    Route::middleware([RoleMiddleware::class.':superadmin,admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Impersonate routes
        Route::post('/users/{user}/impersonate', [ImpersonateController::class, 'impersonate'])->name('users.impersonate');

        // Agent routes
        Route::get('/agents', [AgentController::class, 'index'])->name('agents');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::put('/agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
        Route::post('/agents/{agent}/approve', [AgentController::class, 'approve'])->name('agents.approve');
        Route::post('/agents/{agent}/lock', [AgentController::class, 'lock'])->name('agents.lock');
        Route::post('/agents/{agent}/unlock', [AgentController::class, 'unlock'])->name('agents.unlock');
        Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
        Route::delete('/agents/{agent}/force', [AgentController::class, 'forceDestroy'])->name('agents.force-destroy');
        Route::post('/agents/{agent}/restore', [AgentController::class, 'restore'])->name('agents.restore');
    });

    Route::post('/impersonate/leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');
    

    Route::middleware(['auth:sanctum'])
        ->prefix('admin/ai')
        ->group(function () {
            Route::get('/report/csv', [AiReportController::class, 'exportCsv']);
        });

    Route::post(
        '/admin/users/{user}/unlock',
        [\App\Http\Controllers\Admin\AdminUserController::class, 'unlock']
    )->name('admin.users.unlock');
});

/*
|--------------------------------------------------------------------------
| USER MANAGEMENT AJAX ACTIONS (NO INERTIA)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class])
    ->group(function () {
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/approve', [UserController::class, 'approve']);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    });

/*
|--------------------------------------------------------------------------
| WHATSAPP MENU
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('menu')->name('menu.')->group(function () {
    Route::get('/', [WaMenuController::class, 'index'])->name('index');
    Route::get('/create', [WaMenuController::class, 'create'])->name('create');
    Route::post('/', [WaMenuController::class, 'store'])->name('store');
    Route::get('/{menu}/edit', [WaMenuController::class, 'edit'])->name('edit');
    Route::put('/{menu}', [WaMenuController::class, 'update'])->name('update');
    Route::delete('/{menu}', [WaMenuController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
