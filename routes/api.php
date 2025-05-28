<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaptivePortalController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Captive Portal Authentication API
Route::post('/captive-portal/authenticate', [CaptivePortalController::class, 'authenticate']);

// Test route for debugging
Route::get('/test', function() {
    return response()->json(['message' => 'API is working']);
});
