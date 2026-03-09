<?php
/**
 * PartPal API - Entry Point
 * Version 2.0
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Set timezone
date_default_timezone_set('America/Toronto');

// Start session early
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$appConfig = require __DIR__ . '/../config/app.php';

// CORS Headers - Must be set before any output
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    http_response_code(200);
    exit(0);
}

// Additional CORS headers for all requests
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');

// Initialize Router
use App\Core\Router;
use App\Core\Response;
use App\Core\Logger;
use App\Controllers\AuthController;
use App\Controllers\SupplierController;
use App\Controllers\TechnicianController;
use App\Controllers\UnitController;
use App\Controllers\PartController;
use App\Controllers\WorkOrderController;
use App\Controllers\InventoryController;
use App\Controllers\VendorReturnController;
use App\Controllers\SettingsController;
use App\Controllers\ReportController;

// Log incoming request
Logger::logRequest();

$router = new Router();

// API Info Route
$router->get('/', function() {
    Response::success([
        'name' => 'PAM API',
        'version' => '2.0.0',
        'endpoints' => [
            'auth' => '/api/auth',
            'suppliers' => '/api/suppliers',
            'technicians' => '/api/technicians',
            'units' => '/api/units',
            'parts' => '/api/parts',
            'work_orders' => '/api/work-orders',
            'inventory' => '/api/inventory',
            'vendor_returns' => '/api/vendor-returns',
            'settings' => '/api/settings',
        ]
    ]);
});

// Project README route
$router->get('/readme.html', function() {
    $readmePath = __DIR__ . '/readme.html';
    if (!file_exists($readmePath)) {
        Response::notFound('README not found');
    }

    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    readfile($readmePath);
    exit;
});

// Auth Routes (public - no authentication required)
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout']);
$router->get('/auth/me', [AuthController::class, 'me']);
$router->get('/auth/check', [AuthController::class, 'check']);

// Suppliers Routes
$router->get('/suppliers', [SupplierController::class, 'index']);
$router->get('/suppliers/{id}', [SupplierController::class, 'show']);
$router->post('/suppliers', [SupplierController::class, 'store']);
$router->put('/suppliers/{id}', [SupplierController::class, 'update']);
$router->delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

// Technicians Routes
$router->get('/technicians', [TechnicianController::class, 'index']);
$router->get('/technicians/{id}', [TechnicianController::class, 'show']);
$router->post('/technicians', [TechnicianController::class, 'store']);
$router->put('/technicians/{id}', [TechnicianController::class, 'update']);
$router->delete('/technicians/{id}', [TechnicianController::class, 'destroy']);

// Units Routes
$router->get('/units', [UnitController::class, 'index']);
$router->get('/units/{id}', [UnitController::class, 'show']);
$router->post('/units', [UnitController::class, 'store']);
$router->put('/units/{id}', [UnitController::class, 'update']);
$router->delete('/units/{id}', [UnitController::class, 'destroy']);

// Parts Routes
$router->get('/parts', [PartController::class, 'index']);
$router->get('/parts/low-stock', [PartController::class, 'lowStock']);
$router->get('/parts/check-duplicate', [PartController::class, 'checkDuplicate']);
$router->get('/parts/check-fowler', [PartController::class, 'checkFowlerPartNumber']);
$router->get('/parts/next-fowler', [PartController::class, 'nextFowlerPartNumber']);
$router->get('/parts/fowler-mapping', [PartController::class, 'getFowlerMapping']);
$router->post('/parts/bulk-import', [PartController::class, 'bulkImport']);
$router->get('/parts/{id}', [PartController::class, 'show']);
$router->get('/parts/{id}/stock', [PartController::class, 'getStock']);
$router->get('/parts/{id}/locations', [PartController::class, 'getLocations']);
$router->post('/parts', [PartController::class, 'store']);
$router->put('/parts/{id}', [PartController::class, 'update']);
$router->delete('/parts/{id}', [PartController::class, 'destroy']);

// Work Orders Routes
$router->get('/work-orders', [WorkOrderController::class, 'index']);
$router->get('/work-orders/open', [WorkOrderController::class, 'getOpen']);
$router->get('/work-orders/{id}', [WorkOrderController::class, 'show']);
$router->post('/work-orders', [WorkOrderController::class, 'store']);
$router->put('/work-orders/{id}', [WorkOrderController::class, 'update']);
$router->delete('/work-orders/{id}', [WorkOrderController::class, 'destroy']);

// Inventory Routes
$router->get('/inventory/stock-levels', [InventoryController::class, 'stockLevels']);
$router->get('/inventory/transactions', [InventoryController::class, 'transactions']);
$router->get('/inventory/returnable-items', [InventoryController::class, 'returnableItems']);
$router->post('/inventory/incoming', [InventoryController::class, 'incoming']);
$router->post('/inventory/checkout', [InventoryController::class, 'checkout']);
$router->post('/inventory/return', [InventoryController::class, 'return']);

// Vendor Returns Routes
$router->get('/vendor-returns', [VendorReturnController::class, 'index']);
$router->get('/vendor-returns/{id}', [VendorReturnController::class, 'show']);
$router->post('/vendor-returns', [VendorReturnController::class, 'store']);
$router->put('/vendor-returns/{id}', [VendorReturnController::class, 'update']);
$router->delete('/vendor-returns/{id}', [VendorReturnController::class, 'destroy']);

// Settings Routes
$router->get('/settings', [SettingsController::class, 'show']);
$router->put('/settings', [SettingsController::class, 'update']);
$router->get('/settings/database-tables', [SettingsController::class, 'databaseTables']);
$router->post('/settings/truncate-data', [SettingsController::class, 'truncateData']);

// Reports Routes
$router->get('/reports/summary', [ReportController::class, 'summary']);
$router->get('/reports/archive', [ReportController::class, 'archiveList']);
$router->get('/reports/archive/download', [ReportController::class, 'archiveFile']);
$router->post('/reports/archive', [ReportController::class, 'archiveDownload']);

// Dispatch the request
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\PDOException $e) {
    Logger::error('Database error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    Response::error('Database error: ' . $e->getMessage(), 500);
} catch (\Exception $e) {
    Logger::error('Server error', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    Response::error('Server error: ' . $e->getMessage(), 500);
}
