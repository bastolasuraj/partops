<?php
/**
 * Front Controller
 * 
 * Entry point for all HTTP requests
 */

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap the application
$app = require_once __DIR__ . '/../app/bootstrap.php';

// Handle the request
$app->run();
