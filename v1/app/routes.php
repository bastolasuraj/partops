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
use PartOps\Controllers\LocationController;
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
$router->get('/parts/search', [PartController::class, 'search']);

// Suppliers
$router->get('/suppliers', [SupplierController::class, 'index']);
$router->get('/suppliers/create', [SupplierController::class, 'create']);
$router->post('/suppliers', [SupplierController::class, 'store']);
$router->get('/suppliers/{id}', [SupplierController::class, 'show']);
$router->get('/suppliers/{id}/edit', [SupplierController::class, 'edit']);
$router->post('/suppliers/{id}', [SupplierController::class, 'update']);
$router->post('/suppliers/{id}/delete', [SupplierController::class, 'delete']);

// Locations
$router->get('/locations', [LocationController::class, 'index']);
$router->get('/locations/create', [LocationController::class, 'create']);
$router->post('/locations', [LocationController::class, 'store']);
$router->get('/locations/{id}', [LocationController::class, 'show']);
$router->get('/locations/{id}/edit', [LocationController::class, 'edit']);
$router->post('/locations/{id}', [LocationController::class, 'update']);
$router->post('/locations/{id}/delete', [LocationController::class, 'delete']);

// Inventory
$router->get('/inventory', [InventoryController::class, 'index']);
$router->post('/inventory/receive', [InventoryController::class, 'receive']);
$router->post('/inventory/checkout', [InventoryController::class, 'checkout']);
$router->post('/inventory/return', [InventoryController::class, 'return']);
$router->post('/inventory/adjust', [InventoryController::class, 'adjust']);

// Work Orders
$router->get('/work-orders', [WorkOrderController::class, 'index']);
$router->get('/work-orders/create', [WorkOrderController::class, 'create']);
$router->post('/work-orders', [WorkOrderController::class, 'store']);
$router->get('/work-orders/{id}', [WorkOrderController::class, 'show']);
