<?php
/**
 * Application Configuration
 */
return [
    'name' => 'PAM API',
    'version' => '2.0.0',
    'debug' => getenv('APP_DEBUG') ?: true,
    'timezone' => 'America/Toronto',
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    ],
];
