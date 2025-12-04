<?php

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\PartController;
use App\Controllers\SupplierController;
use App\Controllers\TechnicianController;
use App\Controllers\WorkOrderController;
use App\Controllers\CheckinController;
use App\Controllers\CheckoutController;
use App\Controllers\ReturnController;
use App\Controllers\LogController;

$router = new Router();

// Home
$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'dashboard']);

// Parts
$router->get('/parts', [PartController::class, 'index']);
$router->get('/parts/create', [PartController::class, 'create']);
$router->post('/parts', [PartController::class, 'store']);
$router->get('/parts/{id}', [PartController::class, 'show']);
$router->get('/parts/{id}/edit', [PartController::class, 'edit']);
$router->post('/parts/{id}/update', [PartController::class, 'update']);
$router->post('/parts/{id}/delete', [PartController::class, 'delete']);

// Suppliers
$router->get('/suppliers', [SupplierController::class, 'index']);
$router->post('/suppliers', [SupplierController::class, 'store']);
$router->post('/suppliers/{id}/update', [SupplierController::class, 'update']);
$router->post('/suppliers/{id}/delete', [SupplierController::class, 'delete']);

// Technicians
$router->get('/technicians', [TechnicianController::class, 'index']);
$router->post('/technicians', [TechnicianController::class, 'store']);
$router->post('/technicians/{id}/update', [TechnicianController::class, 'update']);
$router->post('/technicians/{id}/delete', [TechnicianController::class, 'delete']);

// Work Orders
$router->get('/work-orders', [WorkOrderController::class, 'index']);
$router->post('/work-orders', [WorkOrderController::class, 'store']);
$router->get('/work-orders/{id}', [WorkOrderController::class, 'show']);

// Check-ins
$router->get('/checkins', [CheckinController::class, 'index']);
$router->get('/checkins/create', [CheckinController::class, 'create']);
$router->post('/checkins', [CheckinController::class, 'store']);
$router->get('/checkins/work-order', [CheckinController::class, 'workOrderReturn']);
$router->post('/checkins/work-order', [CheckinController::class, 'storeWorkOrderReturn']);

// Checkouts
$router->get('/checkouts', [CheckoutController::class, 'index']);
$router->get('/checkouts/create', [CheckoutController::class, 'create']);
$router->post('/checkouts', [CheckoutController::class, 'store']);

// Returns
$router->get('/returns', [ReturnController::class, 'index']);
$router->get('/returns/supplier', [ReturnController::class, 'supplierReturn']);
$router->post('/returns/supplier', [ReturnController::class, 'storeSupplierReturn']);

// API endpoints for AJAX
$router->get('/api/parts/search', [PartController::class, 'search']);
$router->get('/api/work-orders/{id}/parts', [WorkOrderController::class, 'getParts']);

// Logs (Admin)
$router->get('/logs', [LogController::class, 'index']);
$router->post('/logs/clear', [LogController::class, 'clear']);
$router->get('/logs/download', [LogController::class, 'download']);

return $router;
