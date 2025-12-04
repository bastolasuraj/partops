<?php
declare(strict_types=1);

/**
 * PartOps v2 - Front Controller
 * All requests are routed through this file
 */

define('BASE_PATH', dirname(__DIR__));

// Autoload
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Bootstrap the application
require BASE_PATH . '/app/bootstrap.php';

// Initialize and run the application
$app = new App\Core\Application();
$app->run();
