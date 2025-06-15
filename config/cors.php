<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Allow API routes
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'http://localhost:3001', 'https://*.vercel.app', 'https://*.netlify.app'], // Your Next.js URL in production: ['https://your-next.js-app.com']
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];