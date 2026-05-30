<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'forgot-password', 'reset-password'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([env('FRONTEND_URL', 'http://localhost:5173')]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
