<?php

declare(strict_types=1);

// Start session
session_start();

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load helper functions
require_once __DIR__ . '/../app/Core/helpers.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Set application timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

// Initialize Logger (must be done before any errors can occur)
App\Core\Logger::init();

// Error reporting based on environment
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL); // Still report all errors, but log them
    ini_set('display_errors', '0'); // Don't display to users
    ini_set('log_errors', '1'); // Log to file
}

// Load routes
$router = require __DIR__ . '/../routes/web.php';

// Dispatch the request
$router->dispatch();
