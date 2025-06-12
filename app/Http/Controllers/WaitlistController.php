<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WaitlistController extends Controller
{
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

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the waitlist!',
                'data' => $waitlist
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
                'message' => 'An error occurred while joining the waitlist'
            ], 500);
        }
    }

    /**
     * Get waitList statistics
     */
    public function stats()
    {
        $count = Waitlist::count();
        
        return response()->json([
            'success' => true,
            'data' => [
                'total_subscribers' => $count
            ]
        ]);
    }

    /**
     * Get all waitList entries (Admin only)
     */
    public function index()
    {
        $waitlist = Waitlist::orderBy('joined_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $waitlist
        ]);
    }
}
