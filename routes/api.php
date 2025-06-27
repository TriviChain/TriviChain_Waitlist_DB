<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;

// Add comprehensive OPTIONS handler for CORS preflight requests
Route::options('{any}', function (Request $request) {
    $origin = $request->header('Origin');
    
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:3001', 
        'http://127.0.0.1:3000',
    ];

    $allowedOrigin = '*';

    if ($origin) {
        if (in_array($origin, $allowedOrigins)) {
            $allowedOrigin = $origin;
        } elseif (preg_match('/^https:\/\/.*\.ngrok-free\.app$/', $origin) || 
                 preg_match('/^https:\/\/.*\.ngrok\.io$/', $origin)) {
            $allowedOrigin = $origin;
        }
    }
    return response('', 200)
        ->header('Access-Control-Allow-Origin', $allowedOrigin)
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning, Accept, Origin')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Max-Age', '86400')
        ->header('Content-Type', 'application/json');
})->where('any', '.*');

// Health check route
Route::get('/health', function (Request $request) {
    $response = response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'waitlist-api',
        'version' => '1.0.0',
        'laravel_version' => app()->version()
    ]);

    $origin = $request->header('Origin');
    $allowedOrigin = $origin && (
        in_array($origin, ['http://localhost:3000', 'http://localhost:3001', 'http://127.0.0.1:3000']) ||
        preg_match('/^https:\/\/.*\.ngrok-free\.app$/', $origin) ||
        preg_match('/^https:\/\/.*\.ngrok\.io$/', $origin)
    ) ? $origin : '*';

    return $response->header('Access-Control-Allow-Origin', $allowedOrigin);
});

// Public waitlist routes
// Route::prefix('waitlist')->group(function () {
//     Route::post('/join', [WaitlistController::class, 'join']);
//     Route::get('/stats', [WaitlistController::class, 'stats']);
// });

// Public waitlist routes with explicit CORS middleware
Route::prefix('waitlist')->middleware(['throttle:60,1'])->group(function () {
    Route::post('/join', [WaitlistController::class, 'join']);
    Route::get('/stats', [WaitlistController::class, 'stats']);
    Route::get('/recent', [WaitlistController::class, 'index']);

    // Explicit OPTIONS routes for waitlist endpoints
    Route::options('/join', [WaitlistController::class, 'options']);
    Route::options('/stats', [WaitlistController::class, 'options']);
    Route::options('/recent', [WaitlistController::class, 'options']);
});

// Admin authentication routes
// Route::prefix('admin')->group(function () {
//     Route::post('/login', [AdminAuthController::class, 'login']);
    
//     // Protected admin routes
//     Route::middleware('admin.auth')->group(function () {
//         Route::post('/logout', [AdminAuthController::class, 'logout']);
//         Route::get('/me', [AdminAuthController::class, 'me']);
        
//         // Dashboard routes
//         Route::prefix('dashboard')->group(function () {
//             Route::get('/stats', [AdminDashboardController::class, 'getDashboardStats']);
//             Route::get('/waitlist', [AdminDashboardController::class, 'getWaitlistMembers']);
//             Route::delete('/waitlist/{id}', [AdminDashboardController::class, 'deleteWaitlistMember']);
//             Route::post('/waitlist/{id}/resend-welcome', [AdminDashboardController::class, 'resendWelcomeEmail']);
//             Route::get('/export', [AdminDashboardController::class, 'exportWaitlist']);
//         });
        
//         // Email update routes
//         Route::prefix('emails')->group(function () {
//             Route::post('/send-update', [AdminDashboardController::class, 'sendWaitlistUpdate']);
//             Route::get('/history', [AdminDashboardController::class, 'getEmailUpdateHistory']);
//             Route::get('/history/{id}', [AdminDashboardController::class, 'getEmailUpdateDetails']);
//         });
//     });
// });

// Admin authentication routes (WITH Sanctum middleware for CSRF protection)
Route::prefix('admin')->middleware(['sanctum.stateful'])->group(function () {
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
