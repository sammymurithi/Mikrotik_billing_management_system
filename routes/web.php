<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HotspotUserController;
use App\Http\Controllers\HotspotProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Use CaptivePortalController for the homepage
use App\Http\Controllers\CaptivePortalController;

Route::get('/', [CaptivePortalController::class, 'index']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // System Users
    Route::resource('users', UserController::class);

    // Routers
    Route::resource('routers', RouterController::class);
    Route::get('/routers/{id}/check-connection', [RouterController::class, 'checkConnection'])->name('routers.check-connection');

    // Tickets
    Route::resource('tickets', TicketController::class);
    Route::get('/tickets/{ticket}/download-attachment', [TicketController::class, 'downloadAttachment'])->name('tickets.download-attachment');

    Route::middleware(['auth'])->prefix('hotspot')->name('hotspot.')->group(function () {

        // Hotspot Users 
        Route::resource('users', HotspotUserController::class)->names('users');
        Route::get('users/{id}/sessions', [HotspotUserController::class, 'sessions'])->name('users.sessions');
    
        // Hotspot Profiles
        Route::get('profiles', [HotspotProfileController::class, 'index'])->name('profiles.index');
        Route::post('profiles', [HotspotProfileController::class, 'store'])->name('profiles.store');
        Route::put('profiles/{id}', [HotspotProfileController::class, 'update'])->name('profiles.update');
        Route::delete('profiles/{id}', [HotspotProfileController::class, 'destroy'])->name('profiles.destroy');
    });
});