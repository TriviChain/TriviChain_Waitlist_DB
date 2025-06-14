protected $routeMiddleware = [
    \Illuminate\Http\Middleware\HandleCors::class, // Should already exist
    'admin.auth' => \App\Http\Middleware\AdminAuth::class,
    // Other middleware...
];