<?php
/**
 * Application Routes
 * 
 * Define all HTTP routes for the application
 */

declare(strict_types=1);

use PartOps\Controllers\HomeController;
use PartOps\Controllers\PartController;
use PartOps\Controllers\SupplierController;
use PartOps\Controllers\InventoryController;
use PartOps\Controllers\WorkOrderController;
use PartOps\Controllers\AuthController;

// Home
$router->get('/', [HomeController::class, 'index']);

// Authentication
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Parts
$router->get('/parts', [PartController::class, 'index']);
$router->get('/parts/create', [PartController::class, 'create']);
$router->post('/parts', [PartController::class, 'store']);
$router->get('/parts/{id}', [PartController::class, 'show']);
$router->get('/parts/{id}/edit', [PartController::class, 'edit']);
$router->post('/parts/{id}', [PartController::class, 'update']);
$router->post('/parts/{id}/delete', [PartController::class, 'delete']);
$router->post('/parts/{id}/numbers', [PartController::class, 'storeNumber']);
$router->post('/parts/{id}/numbers/{numberId}/delete', [PartController::class, 'deleteNumber']);
$router->post('/parts/{id}/suppliers', [PartController::class, 'storeSupplier']);
$router->post('/parts/{id}/suppliers/{psId}/delete', [PartController::class, 'deleteSupplier']);
$router->get('/parts/search', [PartController::class, 'search']);

// Suppliers
$router->get('/suppliers', [SupplierController::class, 'index']);
$router->get('/suppliers/create', [SupplierController::class, 'create']);
$router->post('/suppliers', [SupplierController::class, 'store']);
$router->get('/suppliers/{id}', [SupplierController::class, 'show']);
$router->get('/suppliers/{id}/edit', [SupplierController::class, 'edit']);
$router->post('/suppliers/{id}', [SupplierController::class, 'update']);
$router->post('/suppliers/{id}/delete', [SupplierController::class, 'delete']);

// Inventory
$router->get('/inventory', [InventoryController::class, 'index']);
$router->get('/inventory/receive', [InventoryController::class, 'receiveForm']);
$router->post('/inventory/receive', [InventoryController::class, 'processReceive']);
$router->get('/inventory/checkout', [InventoryController::class, 'checkoutForm']);
$router->post('/inventory/checkout', [InventoryController::class, 'processCheckout']);
$router->get('/inventory/return', [InventoryController::class, 'returnForm']);
$router->post('/inventory/return', [InventoryController::class, 'processReturn']);
$router->get('/inventory/adjust', [InventoryController::class, 'adjustForm']);
$router->post('/inventory/adjust', [InventoryController::class, 'processAdjust']);
$router->get('/inventory/search-work-orders', [InventoryController::class, 'searchWorkOrders']);
$router->get('/inventory/work-order-parts', [InventoryController::class, 'getWorkOrderParts']);

// Work Orders
$router->get('/work-orders', [WorkOrderController::class, 'index']);
$router->get('/work-orders/create', [WorkOrderController::class, 'create']);
$router->post('/work-orders', [WorkOrderController::class, 'store']);
$router->get('/work-orders/{id}', [WorkOrderController::class, 'show']);
$router->post('/work-orders/{id}/status', [WorkOrderController::class, 'updateStatus']);
