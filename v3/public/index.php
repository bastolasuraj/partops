<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$autoload = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    die('Please run: composer install');
}
require $autoload;

use Dotenv\Dotenv;
use PartOps\Config\App;
use PartOps\Services\Router;
use PartOps\Services\Session;

if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/error.log');

Session::start();

$router = new Router();
require BASE_PATH . '/src/Config/routes.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

try {
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo 'An error occurred. Please try again later.';
    }
}
