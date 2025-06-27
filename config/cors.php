<?php


return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'http://localhost:3001', 'http://127.0.0.1:3000',],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.ngrok-free\.app$/',
        '/^https:\/\/.*\.ngrok\.io$/',
    ],
    'allowed_headers' => [
        'Content-Type', 
        'X-Requested-With', 
        'Authorization',
        'ngrok-skip-browser-warning', // Add this for ngrok
        'Accept',
        'Origin',
        'User-Agent',
        'DNT',
        'Cache-Control',
        'X-Mx-ReqToken',
        'Keep-Alive',
        'X-Requested-With',
        'If-Modified-Since',
    ],
    'exposed_headers' => [
        'Content-Type',
        'X-Requested-With',
    ],
    'max_age' => 86400, // Cache preflight for 24 hours
    'supports_credentials' => true,
];
