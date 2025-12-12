<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration allows your Vue frontend (localhost:5173) to access
    | your Laravel backend (localhost:8000 or Railway production) without
    | being blocked by browser CORS restrictions.
    |
    */

    // Apply CORS rules to API requests and file access
    'paths' => [
        'api/*',
        'storage/*',      // <-- needed for images
        'sanctum/csrf-cookie',
    ],

    // Allow all HTTP methods (GET, POST, PUT, DELETE, PATCH, etc.)
    'allowed_methods' => ['*'],

    // Allowed origins (your frontend apps)
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        '*', // <-- allow all (safe for now; you can remove later for production)
    ],

    'allowed_origins_patterns' => [],

    // Allow all headers from frontend
    'allowed_headers' => ['*'],

    // Exposed headers (none needed)
    'exposed_headers' => [],

    // No caching of preflight responses
    'max_age' => 0,

    // We are NOT using cookies for API auth → keep this false
    'supports_credentials' => false,
];
