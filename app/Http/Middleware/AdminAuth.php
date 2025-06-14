<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin authentication required.'
            ], 401);
        }

        $user = Auth::guard('sanctum')->user();

        // Check if authenticated user is an admin
        if (!$user instanceof \App\Models\AdminUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        // Check if admin is active
        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive. Contact super admin.'
            ], 403);
        }
        
        return $next($request);
    }
}
