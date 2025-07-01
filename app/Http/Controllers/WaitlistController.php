<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class WaitlistController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }
    
    /**
     * Join the waitList
     */
    public function join(Request $request): JsonResponse
    {
        Log::info('=== WAITLIST JOIN REQUEST STARTED ===', [
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:waitlists,email',
                'name' => 'nullable|string|max:255'
            ]);

            Log::info('✅ VALIDATION PASSED', ['validated_data' => $validated]);

            $waitlist = Waitlist::create([
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
                'joined_at' => now()
            ]);

            Log::info('✅ WAITLIST ENTRY CREATED', [
                'waitlist_id' => $waitlist->id,
                'email' => $waitlist->email,
                'name' => $waitlist->name
            ]);

            // Check email configuration before sending
            $this->debugEmailConfiguration();

            // Send welcome email with detailed error handling
            $emailSent = false;
            $emailError = null;

            try {
                Log::info('📧 STARTING EMAIL SEND PROCESS');
            
                // Test if we can send a simple email first
                $this->testBasicEmailSending($waitlist->email);
            
                // Now send the actual welcome email
                $emailSent = $this->emailService->sendWelcomeEmail($waitlist);
            
                Log::info('✅ EMAIL SENT SUCCESSFULLY', [
                    'email' => $waitlist->email,
                    'email_sent' => $emailSent
                ]);
            
            } catch (\Exception $emailException) {
                $emailError = $emailException->getMessage();
                Log::error('❌ EMAIL SENDING FAILED', [
                    'error' => $emailError,
                    'waitlist_id' => $waitlist->id,
                    'email' => $waitlist->email,
                    'exception_class' => get_class($emailException),
                    'file' => $emailException->getFile(),
                    'line' => $emailException->getLine(),
                    'trace' => $emailException->getTraceAsString()
                ]);
            }

            // Prepare response with email status
            $responseData = [
                'success' => true,
                'message' => 'Successfully joined the waitlist!',
                'data' => [
                    'id' => $waitlist->id,
                    'email' => $waitlist->email,
                    'name' => $waitlist->name,
                    'joined_at' => $waitlist->joined_at,
                ],
                'email_sent' => $emailSent,
                'email_status' => $emailSent ? 'sent' : 'failed'
            ];

            // Always include email error for debugging
            if (!$emailSent) {
                $responseData['email_error'] = $emailError;
                $responseData['debug_info'] = 'Check Laravel logs for detailed error information';
            }

            Log::info('=== WAITLIST JOIN REQUEST COMPLETED ===', [
                'success' => true,
                'email_sent' => $emailSent,
                'email_error' => $emailError
            ]);

            $response = response()->json($responseData, 201);
            return $this->addCorsHeaders($request, $response);

        } catch (ValidationException $e) {
            Log::warning('❌ VALIDATION FAILED', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            $response = response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

            return $this->addCorsHeaders($request, $response);
        } catch (\Exception $e) {
            Log::error('❌ UNEXPECTED ERROR', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $response = response()->json([
                'success' => false,
                'message' => 'An error occurred while joining the waitlist',
                'error' => $e->getMessage(),
                'debug_info' => 'Check Laravel logs for detailed error information'
            ], 500);

            return $this->addCorsHeaders($request, $response);
        }
    }

    /**
     * Debug email configuration
     */
    private function debugEmailConfiguration()
    {
        Log::info('🔍 EMAIL CONFIGURATION DEBUG', [
            'mail_mailer' => config('mail.default'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
            'smtp_username' => config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET',
            'smtp_password' => config('mail.mailers.smtp.password') ? 'SET (length: ' . strlen(config('mail.mailers.smtp.password')) . ')' : 'NOT SET',
            'smtp_encryption' => config('mail.mailers.smtp.encryption'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ]);
    }

    /**
     * Test basic email sending
     */
    private function testBasicEmailSending($toEmail)
    {
        try {
            Log::info('🧪 TESTING BASIC EMAIL SENDING', ['to' => $toEmail]);
        
            Mail::raw('This is a test email to verify SMTP configuration is working.', function ($message) use ($toEmail) {
                $message->to($toEmail)
                        ->subject('Trivichain - Email Test')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
        
            Log::info('✅ BASIC EMAIL TEST PASSED');
        
        } catch (\Exception $e) {
            Log::error('❌ BASIC EMAIL TEST FAILED', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Basic email test failed: ' . $e->getMessage());
        }
    }

   /**
     * Get waitlist statistics
     */
    public function stats(Request $request)
    {
        try {
            $totalCount = Waitlist::count();
            $todayCount = Waitlist::whereDate('created_at', today())->count();
            $weekCount = Waitlist::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count();
            
            // Return data in the format expected by frontend
            $response = response()->json([
                'success' => true,
                'total' => $totalCount,                    // Frontend expects 'total'
                'today' => $todayCount,                    // Frontend expects 'today'
                'total_members' => $totalCount,            // Alternative format
                'today_signups' => $todayCount,            // Alternative format
                'data' => [
                    'total_subscribers' => $totalCount,
                    'joined_today' => $todayCount,
                    'joined_this_week' => $weekCount,
                ]
            ]);

            return $this->addCorsHeaders($request, $response);
        } catch (\Exception $e) {
            Log::error('WaitlistController: Stats error', ['error' => $e->getMessage()]);

            $response = response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'total' => 0,
                'today' => 0,
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);

            return $this->addCorsHeaders($request, $response);
        }
    }

    /**
     * Get all waitlist entries (public - limited info)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $waitlist = Waitlist::select(['id', 'created_at'])
                ->orderBy('joined_at', 'desc')
                ->take(10)
                ->get();
            
            $response = response()->json([
                'success' => true,
                'data' => $waitlist,
                'message' => 'Recent waitlist entries (limited data for privacy)'
            ]);

            return $this->addCorsHeaders($request, $response);
        } catch (\Exception $e) {
            Log::error('WaitlistController: Index error', ['error' => $e->getMessage()]);

            $response = response()->json([
                'success' => false,
                'message' => 'Failed to fetch waitlist entries',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);

            return $this->addCorsHeaders($request, $response);
        }
    }

    /**
     * Add CORS headers to response
     */
    private function addCorsHeaders(Request $request, JsonResponse $response): JsonResponse
    {
        $origin = $request->header('Origin');
        
        // Define allowed origins
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
        ];

        // Check for ngrok patterns
        $allowedOrigin = '*';
        if ($origin) {
            if (in_array($origin, $allowedOrigins)) {
                $allowedOrigin = $origin;
            } elseif (preg_match('/^https:\/\/.*\.ngrok-free\.app$/', $origin) || 
                     preg_match('/^https:\/\/.*\.ngrok\.io$/', $origin)) {
                $allowedOrigin = $origin;
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning, Accept, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Content-Type', 'application/json');
        
        // Add debug headers in development
        if (config('app.debug')) {
            $response->headers->set('X-Debug-Origin', $origin ?: 'none');
            $response->headers->set('X-Debug-Allowed-Origin', $allowedOrigin);
        }

        return $response;
    }

    /**
     * Handle preflight OPTIONS requests
     */
    public function options(Request $request): JsonResponse
    {
        $response = response()->json(['status' => 'ok'], 200);
        return $this->addCorsHeaders($request, $response);
    }
}
