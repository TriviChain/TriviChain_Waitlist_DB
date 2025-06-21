<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    try {
        // Simple health check that doesn't require database
        return response()->json([
            'status' => 'okay',
            'timestamp' => now()->toISOString(),
            'app_key_set' => !empty(config('app.key')),
            'environment' => app()->environment(),
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});
