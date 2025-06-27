<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
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
        try {
            $validated = $request->validate([
                'email' => 'required|email|unique:waitlists,email',
                'name' => 'nullable|string|max:255'
            ]);

            $waitlist = Waitlist::create([
                'email' => $validated['email'],
                'name' => $validated['name'] ?? null,
                'joined_at' => now()
            ]);

            // Send welcome email
            try {
                $this->emailService->sendWelcomeEmail($waitlist);
            } catch (\Exception $emailError) {
                // Log email error but don't fail the request
                Log::warning('Failed to send welcome email: ' . $emailError->getMessage());
            }

            $response = response()->json([
                'success' => true,
                'message' => 'Successfully joined the waitlist!',
                'data' => [
                    'id' => $waitlist->id,
                    'email' => $waitlist->email,
                    'name' => $waitlist->name,
                    'joined_at' => $waitlist->joined_at,
                ]
            ], 201);

            return $this->addCorsHeaders($request, $response);


        } catch (ValidationException $e) {
            $response = response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

            return $this->addCorsHeaders($request, $response);
        } catch (\Exception $e) {
            Log::error('Waitlist join error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            $response = response()->json([
                'success' => false,
                'message' => 'An error occurred while joining the waitlist',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);

            return $this->addCorsHeaders($request, $response);
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
            Log::error('Waitlist stats error: ' . $e->getMessage());

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
            Log::error('Waitlist index error: ' . $e->getMessage());

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
