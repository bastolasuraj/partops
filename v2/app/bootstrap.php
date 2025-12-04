<?php
declare(strict_types=1);

/**
 * PartOps v2 - Application Bootstrap
 */

// Setup error logging
$logFile = BASE_PATH . '/storage/logs/error.log';
$logDir = dirname($logFile);

// Ensure log directory exists
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

// Configure error logging
ini_set('log_errors', '1');
ini_set('error_log', $logFile);

// Error reporting based on environment
if ($_ENV['APP_DEBUG'] ?? false) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL); // Still log all errors
    ini_set('display_errors', '0'); // But don't display them
}

// Custom error handler to log to our file
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Error [$errno]: $errstr in $errfile on line $errline\n";
    @error_log($message, 3, $logFile); // Suppress warnings if file can't be written
    
    // Return false to let PHP's default error handler run as well
    return false;
});

// Custom exception handler
set_exception_handler(function($exception) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Uncaught Exception: " . $exception->getMessage() . "\n";
    $message .= "File: " . $exception->getFile() . " Line: " . $exception->getLine() . "\n";
    $message .= "Stack trace:\n" . $exception->getTraceAsString() . "\n\n";
    @error_log($message, 3, $logFile); // Suppress warnings if file can't be written
    
    // Display user-friendly error
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo 'An error occurred. Please check the error log.';
    }
});

// Log script start (suppress warning if can't write)
@error_log("[" . date('Y-m-d H:i:s') . "] Application started - " . ($_SERVER['REQUEST_URI'] ?? 'CLI') . "\n", 3, $logFile);

// Set timezone
date_default_timezone_set('UTC');

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set default headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
