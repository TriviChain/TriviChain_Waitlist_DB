<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Admin login
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $admin = AdminUser::where('email', $validated['email'])->first();

            if (!$admin || !Hash::check($validated['password'], $admin->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            if (!$admin->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is inactive. Contact super admin.'
                ], 403);
            }

            $token = $admin->createToken('admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'admin' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                    ],
                    'token' => $token,
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Admin logout
     */
    public function logout(Request $request)
    {
        try {
            $admin = Auth::guard('sanctum')->user();
            
            if ($admin) {
                $admin->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    /**
     * Get authenticated admin info
     */
    public function me(Request $request)
    {
        try {
            // $admin = $request->user();

            $admin = Auth::guard('sanctum')->user();
            
            // Check if admin is authenticated
            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'is_active' => $admin->is_active,
                    'created_at' => $admin->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get admin info'
            ], 500);
        }
    }
}
