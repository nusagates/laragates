<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Controllers
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WabaWebhookController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\BroadcastApprovalController;
use App\Http\Controllers\BroadcastReportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Api\Chat\ChatSessionController;
use App\Http\Controllers\Api\Chat\ChatMessageController;
<<<<<<< HEAD
use App\Http\Controllers\ChatController;
use App\Http\Controllers\WaMenuController;
=======
use App\Http\Controllers\Api\Chat\TypingController;
use App\Http\Controllers\ChatController;
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247

use App\Http\Middleware\RoleMiddleware;
use App\Models\Template;

<<<<<<< HEAD

=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
<<<<<<< HEAD
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
=======
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    ]);
});


/*
|--------------------------------------------------------------------------
| EXTRA ROUTE FOR TEMPLATES LIST (Vue Axios)
|--------------------------------------------------------------------------
*/
<<<<<<< HEAD
Route::get('/templates-list', fn () => Template::orderBy('id', 'desc')->get());
=======
Route::get('/templates-list', function () {
    return Template::orderBy('id', 'desc')->get();
});
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247


/*
|--------------------------------------------------------------------------
| AUTH PROTECTED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

<<<<<<< HEAD
    /*
    |--------------------------------------------------------------------------
    | Dashboard & Chat
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/chat', fn () => Inertia::render('Chat/Index'))->name('chat');

    // Heartbeat
    Route::post('/agent/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/agent/offline', [AgentController::class, 'offline']);


=======
    // Dashboard
    Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/chat', fn() => Inertia::render('Chat/Index'))->name('chat');

    // Agent heartbeat
    Route::post('/agent/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/agent/offline', [AgentController::class, 'offline']);

>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    */
<<<<<<< HEAD
    Route::get('/analytics', fn () => Inertia::render('Analytics/AnalyticsDashboard'))
=======
    Route::get('/analytics', fn() => Inertia::render('Analytics/AnalyticsDashboard'))
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        ->name('analytics');

    Route::prefix('analytics')->group(function () {
        Route::get('/metrics', [AnalyticsController::class, 'metrics']);
        Route::get('/trends', [AnalyticsController::class, 'trends']);
        Route::get('/agents', [AnalyticsController::class, 'agents']);
        Route::get('/response-time', [AnalyticsController::class, 'responseTime']);
        Route::get('/peakhours', [AnalyticsController::class, 'peakHours']);
        Route::get('/sessions', [AnalyticsController::class, 'sessions']);
    });

<<<<<<< HEAD

=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    /*
    |--------------------------------------------------------------------------
    | Chat Advanced
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

<<<<<<< HEAD
=======
        // outbound message
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        Route::post('/sessions/outbound', [ChatController::class, 'outbound'])
            ->name('chat.outbound');
    });

<<<<<<< HEAD

=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    /*
    |--------------------------------------------------------------------------
    | Tickets
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');

<<<<<<< HEAD

=======
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    /*
    |--------------------------------------------------------------------------
    | Templates
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

        Route::post('/{template}/send', [TemplateController::class, 'send'])->name('templates.send');

        // Versioning
<<<<<<< HEAD
        Route::post('/{template}/versions', [TemplateController::class, 'saveVersion'])
            ->name('templates.version.save');
        Route::post('/{template}/versions/{version}/revert', [TemplateController::class, 'revertVersion'])
            ->name('templates.version.revert');

        // Notes
        Route::post('/{template}/notes', [TemplateController::class, 'saveNote'])
            ->name('templates.notes.save');
    });


=======
        Route::post('/{template}/versions', [TemplateController::class, 'saveVersion'])->name('templates.version.save');
        Route::post('/{template}/versions/{version}/revert', [TemplateController::class, 'revertVersion'])->name('templates.version.revert');

        // Notes
        Route::post('/{template}/notes', [TemplateController::class, 'saveNote'])->name('templates.notes.save');
    });

>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    /*
    |--------------------------------------------------------------------------
    | Broadcast
    |--------------------------------------------------------------------------
    */
    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast');

<<<<<<< HEAD
    Route::post('/broadcast/campaigns', [BroadcastController::class, 'store'])->name('broadcast.store');

    Route::post(
        '/broadcast/campaigns/{campaign}/upload-targets',
        [BroadcastController::class, 'uploadTargets']
    )->name('broadcast.upload-targets');


=======
    Route::post('/broadcast/campaigns', 
        [BroadcastController::class, 'store']
    )->name('broadcast.store');

    Route::post('/broadcast/campaigns/{campaign}/upload-targets',
        [BroadcastController::class, 'uploadTargets']
    )->name('broadcast.upload-targets');

>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware([RoleMiddleware::class . ':superadmin'])->group(function () {

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/general', [SettingController::class, 'saveGeneral']);
        Route::post('/settings/waba', [SettingController::class, 'saveWaba']);
        Route::post('/settings/preferences', [SettingController::class, 'savePreferences']);

        // Broadcast approvals
        Route::get('/broadcast/approvals', [BroadcastApprovalController::class, 'index'])
            ->name('broadcast.approvals.index');

        Route::post('/broadcast/{approval}/approve', [BroadcastApprovalController::class, 'approve']);
        Route::post('/broadcast/{approval}/reject', [BroadcastApprovalController::class, 'reject']);

        // Reports
        Route::get('/broadcast/report', [BroadcastReportController::class, 'index'])
            ->name('broadcast.report.index');

        Route::get('/broadcast/report/{campaign}', [BroadcastReportController::class, 'show'])
            ->name('broadcast.report.show');

<<<<<<< HEAD
        // Agents
=======
        // Agents management
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
        Route::get('/agents', [AgentController::class, 'index'])->name('agents');
        Route::post('/agents', [AgentController::class, 'store'])->name('agents.store');
        Route::post('/agents/{user}/approve', [AgentController::class, 'approve'])->name('agents.approve');
        Route::put('/agents/{user}', [AgentController::class, 'update'])->name('agents.update');
        Route::patch('/agents/{user}/status', [AgentController::class, 'updateStatus'])->name('agents.status');
        Route::delete('/agents/{user}', [AgentController::class, 'destroy'])->name('agents.destroy');
    });
<<<<<<< HEAD


    /*
    |--------------------------------------------------------------------------
    | WHATSAPP MENU (✅ FIXED)
    |--------------------------------------------------------------------------
    */
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [WaMenuController::class, 'index'])->name('index');
        Route::get('/create', [WaMenuController::class, 'create'])->name('create');
        Route::post('/', [WaMenuController::class, 'store'])->name('store');
        Route::get('/{menu}/edit', [WaMenuController::class, 'edit'])->name('edit');
        Route::put('/{menu}', [WaMenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [WaMenuController::class, 'destroy'])->name('destroy');
    });

}); // END AUTH GROUP ✔ FIXED


/*
|--------------------------------------------------------------------------
| PROFILE
=======
});

/*
|--------------------------------------------------------------------------
| Profile
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

<<<<<<< HEAD

/*
|--------------------------------------------------------------------------
| WEBHOOK WA (Meta)
=======
/*
|--------------------------------------------------------------------------
| WEBHOOK WA
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
|--------------------------------------------------------------------------
*/
Route::get('/webhook/whatsapp', [WabaWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WabaWebhookController::class, 'receive']);

<<<<<<< HEAD
require __DIR__ . '/auth.php';
=======
require __DIR__.'/auth.php';
>>>>>>> 7761fb9027cea6c368ca3c824f9926b5a719e247
