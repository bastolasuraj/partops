<?php
/**
 * Application Bootstrap
 * 
 * Initialize configuration, database, and routing
 */

declare(strict_types=1);

use PartOps\Config\Config;
use PartOps\Config\Database;
use PartOps\Core\Application;
use PartOps\Core\Router;

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Load environment configuration
$config = Config::load(__DIR__ . '/../.env');

// Define base URL constant and helper function
define('APP_URL', rtrim($config->get('APP_URL', ''), '/'));

if (!function_exists('url')) {
    function url(string $path = ''): string {
        return APP_URL . '/' . ltrim($path, '/');
    }
}

// Set error reporting based on environment
if ($config->get('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

// Initialize database connection
$db = Database::connect($config);

// Create router
$router = new Router();

// Load routes
require_once __DIR__ . '/routes.php';

// Create and return application instance
return new Application($config, $db, $router);
