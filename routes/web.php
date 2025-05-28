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

// Router Configuration Controller
use App\Http\Controllers\RouterConfigurationController;

use App\Http\Controllers\VoucherController;

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
    Route::post('/routers/{id}/restart', [RouterController::class, 'restart'])->name('routers.restart');
    Route::post('/routers/{id}/reset-configuration', [RouterController::class, 'resetConfiguration'])->name('routers.reset-configuration');
    
    // Interface Management
    Route::get('/routers/{id}/interfaces', [RouterController::class, 'getInterfaces'])->name('routers.interfaces.index');
    Route::post('/routers/{id}/interfaces', [RouterController::class, 'createInterface'])->name('routers.interfaces.create');
    Route::put('/routers/{id}/interfaces/{interfaceId}', [RouterController::class, 'updateInterface'])->name('routers.interfaces.update');
    Route::delete('/routers/{id}/interfaces/{interfaceId}', [RouterController::class, 'deleteInterface'])->name('routers.interfaces.delete');
    Route::post('/routers/{id}/interfaces/{interfaceId}/toggle', [RouterController::class, 'toggleInterfaceStatus'])->name('routers.interfaces.toggle');

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
        Route::post('profiles/sync', [HotspotProfileController::class, 'syncProfiles'])->name('profiles.sync');
    });

    // Router Configuration Routes
    // Route::get('/routers/{router}/configure', [RouterConfigurationController::class, 'show'])
    //     ->name('routers.configure.show');
    // Route::get('/routers/{router}/configure/{tab}', [RouterConfigurationController::class, 'show'])
    //     ->name('routers.configure.get');
    // Route::post('/routers/{router}/configure/{tab}', [RouterConfigurationController::class, 'configure'])
    //     ->name('routers.configure');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::resource('vouchers', VoucherController::class)->only(['index', 'store', 'destroy']);
        Route::post('vouchers/{voucher}/disable', [VoucherController::class, 'disable'])->name('vouchers.disable');
        Route::post('vouchers/batch-delete', [VoucherController::class, 'batchDelete'])->name('vouchers.batch-delete');
        Route::post('vouchers/batch-disable', [VoucherController::class, 'batchDisable'])->name('vouchers.batch-disable');
    });

    // Captive Portal Routes
    Route::get('/', [CaptivePortalController::class, 'index'])->name('welcome');
    
    // Voucher Test Page
    Route::get('/voucher-test', function() {
        return Inertia::render('VoucherTest');
    })->name('voucher.test');

});