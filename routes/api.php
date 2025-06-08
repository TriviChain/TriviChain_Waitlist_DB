<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaitlistController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes
Route::post('/waitlist/join', [WaitlistController::class, 'join']);
Route::get('/waitlist/stats', [WaitlistController::class, 'stats']);

// Admin routes (you can add authentication later)
Route::get('/waitlist', [WaitlistController::class, 'index']);
