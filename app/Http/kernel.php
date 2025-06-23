protected $routeMiddleware = [
    \Illuminate\Http\Middleware\HandleCors::class, // Should already exist
    'admin.auth' => \App\Http\Middleware\AdminAuth::class,
     \Fruitcake\Cors\HandleCors::class,
    // Other middleware...
];