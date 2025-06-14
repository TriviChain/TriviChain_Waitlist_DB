<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use App\Services\EmailService;
use Illuminate\Http\Request;
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
    public function join(Request $request)
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
            $this->emailService->sendWelcomeEmail($waitlist);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the waitlist!',
                'data' => [
                    'id' => $waitlist->id,
                    'email' => $waitlist->email,
                    'name' => $waitlist->name,
                    'joined_at' => $waitlist->joined_at,
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while joining the waitlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

   /**
     * Get waitlist statistics
     */
    public function stats()
    {
        try {
            $count = Waitlist::count();
            $todayCount = Waitlist::whereDate('created_at', today())->count();
            $weekCount = Waitlist::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_subscribers' => $count,
                    'joined_today' => $todayCount,
                    'joined_this_week' => $weekCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all waitlist entries (public - limited info)
     */
    public function index()
    {
        try {
            $waitlist = Waitlist::select(['id', 'created_at'])
                ->orderBy('joined_at', 'desc')
                ->take(10)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $waitlist,
                'message' => 'Recent waitlist entries (limited data for privacy)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waitlist entries',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
