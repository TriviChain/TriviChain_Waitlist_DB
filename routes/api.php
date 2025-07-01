<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WaitlistController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Services\EmailService;
use Illuminate\Support\Facades\Mail;

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

// Comprehensive Email testing routes
Route::prefix('email-test')->group(function () {
    
    // 1. Check email configuration
    Route::get('check', function () {
        return response()->json([
            'mail_config' => [
                'default_mailer' => config('mail.default'),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_username' => config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET',
                'smtp_password' => config('mail.mailers.smtp.password') ? 'SET (length: ' . strlen(config('mail.mailers.smtp.password')) . ')' : 'NOT SET',
                'smtp_encryption' => config('mail.mailers.smtp.encryption'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
            'env_check' => [
                'MAIL_MAILER' => env('MAIL_MAILER'),
                'MAIL_HOST' => env('MAIL_HOST'),
                'MAIL_PORT' => env('MAIL_PORT'),
                'MAIL_USERNAME' => env('MAIL_USERNAME') ? 'SET' : 'NOT SET',
                'MAIL_PASSWORD' => env('MAIL_PASSWORD') ? 'SET (length: ' . strlen(env('MAIL_PASSWORD')) . ')' : 'NOT SET',
                'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
                'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
            ]
        ]);
    });
    
    // 2. Test basic SMTP connection
    Route::get('smtp', function (Request $request) {
        try {
            $email = $request->query('email', config('mail.from.address'));
            
            Mail::raw('SMTP Connection Test - If you receive this, your SMTP configuration is working!', function ($message) use ($email) {
                $message->to($email)
                        ->subject('SMTP Test - ' . now()->format('Y-m-d H:i:s'))
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            return response()->json([
                'success' => true,
                'message' => 'SMTP test email sent successfully!',
                'sent_to' => $email,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP test failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    });
    
    // 3. Test simple email via EmailService
    Route::get('simple', function (Request $request) {
        try {
            $email = $request->query('email', config('mail.from.address'));
            $emailService = new EmailService();
            $emailService->sendSimpleTestEmail($email);
            
            return response()->json([
                'success' => true,
                'message' => 'Simple test email sent successfully!',
                'sent_to' => $email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Simple test email failed',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // 4. Test email configuration via EmailService
    Route::get('config', function () {
        try {
            $emailService = new EmailService();
            $emailService->testEmailConfiguration();
            
            return response()->json([
                'success' => true,
                'message' => 'Email configuration test successful! Check your inbox.',
                'config' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'username' => config('mail.mailers.smtp.username'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email configuration test failed',
                'error' => $e->getMessage(),
                'config_check' => [
                    'host' => config('mail.mailers.smtp.host') ? 'SET' : 'MISSING',
                    'username' => config('mail.mailers.smtp.username') ? 'SET' : 'MISSING',
                    'password' => config('mail.mailers.smtp.password') ? 'SET' : 'MISSING',
                    'from_address' => config('mail.from.address') ? 'SET' : 'MISSING',
                ]
            ], 500);
        }
    });

    // 5. Test welcome email template
    Route::post('welcome', function (Request $request) {
        try {
            $email = $request->input('email', config('mail.from.address'));
            $name = $request->input('name', 'Test User');
            
            // Create test waitlist entry (not saved to database)
            $testWaitlist = new \App\Models\Waitlist([
                'id' => 999,
                'email' => $email,
                'name' => $name,
                'joined_at' => now()
            ]);
            
            $emailService = new EmailService();
            $emailService->sendWelcomeEmail($testWaitlist);
            
            return response()->json([
                'success' => true,
                'message' => 'Welcome email sent successfully!',
                'sent_to' => $email,
                'test_data' => [
                    'name' => $name,
                    'email' => $email,
                    'joined_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send welcome email',
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // 6. Test all email functionality in sequence
    Route::get('all', function (Request $request) {
        $email = $request->query('email', config('mail.from.address'));
        $results = [];
        
        // Test 1: Configuration check
        try {
            $emailService = new EmailService();
            $emailService->testEmailConfiguration();
            $results['config_test'] = ['success' => true, 'message' => 'Configuration valid'];
        } catch (\Exception $e) {
            $results['config_test'] = ['success' => false, 'error' => $e->getMessage()];
        }
        
        // Test 2: Simple email
        try {
            $emailService = new EmailService();
            $emailService->sendSimpleTestEmail($email);
            $results['simple_email'] = ['success' => true, 'message' => 'Simple email sent'];
        } catch (\Exception $e) {
            $results['simple_email'] = ['success' => false, 'error' => $e->getMessage()];
        }
        
        // Test 3: Welcome email template
        try {
            $testWaitlist = new \App\Models\Waitlist([
                'id' => 999,
                'email' => $email,
                'name' => 'Test User',
                'joined_at' => now()
            ]);
            
            $emailService = new EmailService();
            $emailService->sendWelcomeEmail($testWaitlist);
            $results['welcome_email'] = ['success' => true, 'message' => 'Welcome email sent'];
        } catch (\Exception $e) {
            $results['welcome_email'] = ['success' => false, 'error' => $e->getMessage()];
        }
        
        return response()->json([
            'test_email' => $email,
            'timestamp' => now()->toISOString(),
            'results' => $results,
            'overall_success' => collect($results)->every(fn($result) => $result['success'])
        ]);
    });
});

// Debug routes
Route::get('/debug/routes', function () {
    return response()->json([
        'available_routes' => [
            'GET /api/health',
            'GET /api/waitlist/stats',
            'POST /api/waitlist/join',
            'GET /api/waitlist/recent',
            'GET /api/email-test/check - Check email configuration',
            'GET /api/email-test/smtp?email=test@example.com - Test SMTP connection',
            'GET /api/email-test/simple?email=test@example.com - Test simple email',
            'GET /api/email-test/config - Test email service configuration',
            'POST /api/email-test/welcome - Test welcome email template',
            'GET /api/email-test/all?email=test@example.com - Run all email tests'
        ],
        'current_time' => now(),
        'app_url' => config('app.url')
    ]);
});

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
