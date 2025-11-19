<?php

use App\Http\Controllers\ProfileController;
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
    Route::get('/agents', fn () => Inertia::render('Agents/Index'))->name('agents');

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

require __DIR__.'/auth.php';
