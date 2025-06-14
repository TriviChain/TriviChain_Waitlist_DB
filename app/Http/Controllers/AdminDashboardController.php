<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use App\Models\EmailUpdate;
use App\Models\AdminUser;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->middleware('admin.auth');
        $this->emailService = $emailService;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            $totalWaitlist = Waitlist::count();
            $todayJoined = Waitlist::whereDate('created_at', today())->count();
            $thisWeekJoined = Waitlist::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count();
            $thisMonthJoined = Waitlist::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $totalEmailsSent = EmailUpdate::sum('sent_count');
            $totalEmailUpdates = EmailUpdate::count();
            $welcomeEmailsSent = Waitlist::where('welcome_email_sent', true)->count();
            $pendingWelcomeEmails = Waitlist::where('welcome_email_sent', false)->count();

            // Recent activity
            $recentJoins = Waitlist::latest()
                ->take(10)
                ->get(['id', 'email', 'name', 'created_at']);

            $recentUpdates = EmailUpdate::with('admin:id,name')
                ->latest()
                ->take(5)
                ->get(['id', 'subject', 'recipients_count', 'sent_count', 'status', 'sent_by', 'created_at']);

            // Growth data for charts (last 30 days)
            $growthData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $count = Waitlist::whereDate('created_at', $date)->count();
                $growthData[] = [
                    'date' => $date->format('M d'),
                    'count' => $count,
                    'cumulative' => Waitlist::whereDate('created_at', '<=', $date)->count()
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_waitlist' => $totalWaitlist,
                        'today_joined' => $todayJoined,
                        'week_joined' => $thisWeekJoined,
                        'month_joined' => $thisMonthJoined,
                        'total_emails_sent' => $totalEmailsSent,
                        'total_email_updates' => $totalEmailUpdates,
                        'welcome_emails_sent' => $welcomeEmailsSent,
                        'pending_welcome_emails' => $pendingWelcomeEmails,
                    ],
                    'recent_joins' => $recentJoins,
                    'recent_updates' => $recentUpdates,
                    'growth_data' => $growthData,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all waitlist members with pagination and search
     */
    public function getWaitlistMembers(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Waitlist::query();

            // Search functionality
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $allowedSortFields = ['created_at', 'email', 'name', 'updates_received'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $waitlistMembers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $waitlistMembers,
                'meta' => [
                    'total' => $waitlistMembers->total(),
                    'per_page' => $waitlistMembers->perPage(),
                    'current_page' => $waitlistMembers->currentPage(),
                    'last_page' => $waitlistMembers->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch waitlist members',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Send update to all waitlist members
     */
    public function sendWaitlistUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:5000',
            ]);

            $admin = Auth::user();
            
            // Check if there are waitlist members
            $waitlistCount = Waitlist::count();
            if ($waitlistCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No waitlist members to send updates to.'
                ], 400);
            }

            // Send the update
            $emailUpdate = $this->emailService->sendWaitlistUpdate(
                $validated['subject'],
                $validated['message'],
                $admin->id
            );

            return response()->json([
                'success' => true,
                'message' => "Update email queued successfully! Sending to {$waitlistCount} members.",
                'data' => [
                    'update_id' => $emailUpdate->id,
                    'subject' => $emailUpdate->subject,
                    'recipients_count' => $emailUpdate->recipients_count,
                    'status' => $emailUpdate->status,
                    'sent_at' => $emailUpdate->sent_at,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send waitlist update',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


/**
     * Get email update history
     */
    public function getEmailUpdateHistory(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            
            $updates = EmailUpdate::with('admin:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $updates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email update history',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get specific email update details
     */
    public function getEmailUpdateDetails($id)
    {
        try {
            $update = EmailUpdate::with('admin:id,name,email')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $update
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Email update not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch email update details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Resend welcome email to specific member
     */
    public function resendWelcomeEmail($id)
    {
        try {
            $member = Waitlist::findOrFail($id);
            
            $success = $this->emailService->sendWelcomeEmail($member);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome email queued successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to queue welcome email'
                ], 500);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Waitlist member not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend welcome email',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Export waitlist members to CSV
     */
    public function exportWaitlist()
    {
        try {
            $members = Waitlist::orderBy('created_at', 'desc')->get();
            
            $csvData = [];
            $csvData[] = ['Name', 'Email', 'Joined Date', 'Welcome Email Sent', 'Updates Received', 'Last Update Received'];
            
            foreach ($members as $member) {
                $csvData[] = [
                    $member->name ?? 'N/A',
                    $member->email,
                    $member->created_at->format('Y-m-d H:i:s'),
                    $member->welcome_email_sent ? 'Yes' : 'No',
                    $member->updates_received,
                    $member->last_update_received_at ? $member->last_update_received_at->format('Y-m-d H:i:s') : 'Never'
                ];
            }

            $filename = 'waitlist_export_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            return response()->json([
                'success' => true,
                'message' => 'Export data prepared',
                'data' => [
                    'filename' => $filename,
                    'csv_data' => $csvData,
                    'total_records' => count($csvData) - 1 // Exclude header
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export waitlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
