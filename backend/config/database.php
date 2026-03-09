<?php
/**
 * Database Configuration
 */
return [
    'host' => getenv('DB_HOST') ?: '192.168.3.4',
    'database' => getenv('DB_NAME') ?: 'partsam',
    'username' => getenv('DB_USER') ?: 'pr',
    'password' => getenv('DB_PASS') ?: 'eu9MB6!fh2@PR',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
