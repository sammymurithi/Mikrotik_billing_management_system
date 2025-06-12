<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaptivePortalController;
use App\Http\Controllers\PaymentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Captive Portal Authentication API
Route::post('/captive-portal/authenticate', [CaptivePortalController::class, 'authenticate']);

// Test route for debugging
Route::get('/test', function() {
    return response()->json(['message' => 'API is working']);
});

// Payment Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment']);
    Route::get('/payments/transactions', [PaymentController::class, 'getTransactions']);
    Route::get('/payments/stats', [PaymentController::class, 'getTransactionStats']);
});

Route::post('/payments/mpesa/callback', [PaymentController::class, 'handleMpesaCallback']);
