<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Health check
// Route::get('/health', function () {
//     return response()->json([
//         'status' => 'ok',
//         'timestamp' => now(),
//         'service' => 'waitlist-api'
//     ]);
// });

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// Public waitlist routes
Route::prefix('waitlist')->group(function () {
    Route::post('/join', [WaitlistController::class, 'join']);
    Route::get('/stats', [WaitlistController::class, 'stats']);
});

// Admin authentication routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
    
    // Protected admin routes
    Route::middleware('admin.auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
        
        // Dashboard routes
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [AdminDashboardController::class, 'getDashboardStats']);
            Route::get('/waitlist', [AdminDashboardController::class, 'getWaitlistMembers']);
            Route::delete('/waitlist/{id}', [AdminDashboardController::class, 'deleteWaitlistMember']);
            Route::post('/waitlist/{id}/resend-welcome', [AdminDashboardController::class, 'resendWelcomeEmail']);
            Route::get('/export', [AdminDashboardController::class, 'exportWaitlist']);
        });
        
        // Email update routes
        Route::prefix('emails')->group(function () {
            Route::post('/send-update', [AdminDashboardController::class, 'sendWaitlistUpdate']);
            Route::get('/history', [AdminDashboardController::class, 'getEmailUpdateHistory']);
            Route::get('/history/{id}', [AdminDashboardController::class, 'getEmailUpdateDetails']);
        });
    });
});

// Rate limited routes
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/waitlist/join', [WaitlistController::class, 'join']);
});
