<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RouterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HotspotUserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

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

    // Hotspot Users
    Route::get('/hotspot/users', [HotspotUserController::class, 'index'])->name('hotspot.users.index');
    Route::get('/hotspot/users/create', [HotspotUserController::class, 'create'])->name('hotspot.users.create');
    Route::post('/hotspot/users', [HotspotUserController::class, 'store'])->name('hotspot.users.store');
    Route::get('/hotspot/users/{id}', [HotspotUserController::class, 'show'])->name('hotspot.users.show');
    Route::get('/hotspot/users/{id}/edit', [HotspotUserController::class, 'edit'])->name('hotspot.users.edit');
    Route::put('/hotspot/users/{id}', [HotspotUserController::class, 'update'])->name('hotspot.users.update');
    Route::delete('/hotspot/users/{id}', [HotspotUserController::class, 'destroy'])->name('hotspot.users.destroy');
    Route::get('/hotspot/users/{id}/sessions', [HotspotUserController::class, 'sessions'])->name('hotspot.users.sessions');
});