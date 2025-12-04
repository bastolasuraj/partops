<?php
declare(strict_types=1);

use PartOps\Controllers\AuthController;
use PartOps\Controllers\DashboardController;
use PartOps\Controllers\PartsController;
use PartOps\Controllers\SuppliersController;
use PartOps\Controllers\LocationsController;
use PartOps\Controllers\TechniciansController;
use PartOps\Controllers\ReceivingController;
use PartOps\Controllers\CheckoutController;
use PartOps\Controllers\ReturnsController;
use PartOps\Controllers\QRController;
use PartOps\Controllers\ReportsController;
use PartOps\Controllers\AdminController;
use PartOps\Middleware\AuthMiddleware;
use PartOps\Middleware\AdminMiddleware;
use PartOps\Middleware\CSRFMiddleware;
use PartOps\Middleware\RateLimitMiddleware;

$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login'], [CSRFMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);

$router->get('/parts', [PartsController::class, 'index'], [AuthMiddleware::class]);
$router->get('/parts/new', [PartsController::class, 'create'], [AuthMiddleware::class]);
$router->post('/parts', [PartsController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->get('/parts/{id}', [PartsController::class, 'show'], [AuthMiddleware::class]);
$router->get('/parts/{id}/edit', [PartsController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/parts/{id}', [PartsController::class, 'update'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/parts/{id}/delete', [PartsController::class, 'delete'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->get('/api/parts/search', [PartsController::class, 'apiSearch'], [AuthMiddleware::class]);

$router->get('/suppliers', [SuppliersController::class, 'index'], [AuthMiddleware::class]);
$router->get('/suppliers/new', [SuppliersController::class, 'create'], [AuthMiddleware::class]);
$router->post('/suppliers', [SuppliersController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->get('/suppliers/{id}', [SuppliersController::class, 'show'], [AuthMiddleware::class]);
$router->get('/suppliers/{id}/edit', [SuppliersController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/suppliers/{id}', [SuppliersController::class, 'update'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/suppliers/{id}/delete', [SuppliersController::class, 'delete'], [AuthMiddleware::class, CSRFMiddleware::class]);

$router->get('/locations', [LocationsController::class, 'index'], [AuthMiddleware::class]);
$router->get('/locations/new', [LocationsController::class, 'create'], [AuthMiddleware::class]);
$router->post('/locations', [LocationsController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->get('/locations/{id}', [LocationsController::class, 'show'], [AuthMiddleware::class]);
$router->get('/locations/{id}/edit', [LocationsController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/locations/{id}', [LocationsController::class, 'update'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/locations/{id}/delete', [LocationsController::class, 'delete'], [AuthMiddleware::class, CSRFMiddleware::class]);

$router->get('/technicians', [TechniciansController::class, 'index'], [AuthMiddleware::class]);
$router->get('/technicians/new', [TechniciansController::class, 'create'], [AuthMiddleware::class]);
$router->post('/technicians', [TechniciansController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->get('/technicians/{id}/edit', [TechniciansController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/technicians/{id}', [TechniciansController::class, 'update'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->post('/technicians/{id}/delete', [TechniciansController::class, 'delete'], [AuthMiddleware::class, CSRFMiddleware::class]);
$router->get('/api/technicians/search', [TechniciansController::class, 'apiSearch'], [AuthMiddleware::class]);

$router->get('/receiving', [ReceivingController::class, 'index'], [AuthMiddleware::class]);
$router->post('/receiving', [ReceivingController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class, RateLimitMiddleware::class]);

$router->get('/checkout', [CheckoutController::class, 'index'], [AuthMiddleware::class]);
$router->post('/checkout', [CheckoutController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class, RateLimitMiddleware::class]);

$router->get('/returns', [ReturnsController::class, 'index'], [AuthMiddleware::class]);
$router->post('/returns', [ReturnsController::class, 'store'], [AuthMiddleware::class, CSRFMiddleware::class, RateLimitMiddleware::class]);
$router->post('/returns/core-status', [ReturnsController::class, 'updateCoreStatus'], [AuthMiddleware::class, CSRFMiddleware::class]);

$router->get('/qr', [QRController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/qr/lookup', [QRController::class, 'lookup'], [AuthMiddleware::class]);
$router->post('/api/qr/add', [QRController::class, 'quickAdd'], [AuthMiddleware::class, CSRFMiddleware::class, RateLimitMiddleware::class]);
$router->post('/api/qr/remove', [QRController::class, 'quickRemove'], [AuthMiddleware::class, CSRFMiddleware::class, RateLimitMiddleware::class]);
$router->get('/api/qr/generate/{id}', [QRController::class, 'generateCode'], [AuthMiddleware::class]);

$router->get('/reports', [ReportsController::class, 'index'], [AuthMiddleware::class]);
$router->get('/reports/low-stock', [ReportsController::class, 'lowStock'], [AuthMiddleware::class]);
$router->get('/reports/core-liabilities', [ReportsController::class, 'coreLiabilities'], [AuthMiddleware::class]);
$router->get('/reports/technician-usage', [ReportsController::class, 'technicianUsage'], [AuthMiddleware::class]);
$router->get('/reports/audit-log', [ReportsController::class, 'auditLog'], [AuthMiddleware::class]);

$router->get('/admin', [AdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/users/new', [AdminController::class, 'createUser'], [AdminMiddleware::class]);
$router->post('/admin/users', [AdminController::class, 'storeUser'], [AdminMiddleware::class, CSRFMiddleware::class]);
$router->post('/admin/users/{id}/toggle', [AdminController::class, 'toggleUser'], [AdminMiddleware::class, CSRFMiddleware::class]);
