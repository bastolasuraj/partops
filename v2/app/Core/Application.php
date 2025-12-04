<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Main Application Class
 * This file should be moved to: app/Core/Application.php
 */
class Application
{
    private Router $router;
    private Request $request;
    private Response $response;
    private ?Controller $controller = null;

    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Home
        $this->router->get('/', 'HomeController@index');
        
        // Authentication
        $this->router->get('/login', 'AuthController@showLogin');
        $this->router->post('/login', 'AuthController@login');
        $this->router->get('/logout', 'AuthController@logout');
        
        // Parts
        $this->router->get('/parts', 'PartController@index');
        $this->router->get('/parts/create', 'PartController@create');
        $this->router->post('/parts', 'PartController@store');
        $this->router->get('/parts/{id}', 'PartController@show');
        $this->router->get('/parts/{id}/edit', 'PartController@edit');
        $this->router->post('/parts/{id}', 'PartController@update');
        $this->router->post('/parts/{id}/delete', 'PartController@delete');
        
        // Receiving
        $this->router->get('/receiving', 'ReceivingController@index');
        $this->router->get('/receiving/new', 'ReceivingController@createNew');
        $this->router->post('/receiving/new', 'ReceivingController@storeNew');
        $this->router->get('/receiving/wo-return', 'ReceivingController@createWoReturn');
        $this->router->post('/receiving/wo-return', 'ReceivingController@storeWoReturn');
        
        // Checkout
        $this->router->get('/checkout', 'CheckoutController@index');
        $this->router->get('/checkout/create', 'CheckoutController@create');
        $this->router->post('/checkout', 'CheckoutController@store');
        
        // Returns
        $this->router->get('/returns', 'ReturnController@index');
        $this->router->get('/returns/standard', 'ReturnController@createStandard');
        $this->router->post('/returns/standard', 'ReturnController@storeStandard');
        $this->router->get('/returns/core', 'ReturnController@createCore');
        $this->router->post('/returns/core', 'ReturnController@storeCore');
        
        // Locations
        $this->router->get('/locations', 'LocationController@index');
        $this->router->get('/locations/create', 'LocationController@create');
        $this->router->post('/locations', 'LocationController@store');
        $this->router->get('/locations/{id}/edit', 'LocationController@edit');
        $this->router->post('/locations/{id}', 'LocationController@update');
        
        // Suppliers
        $this->router->get('/suppliers', 'SupplierController@index');
        $this->router->get('/suppliers/create', 'SupplierController@create');
        $this->router->post('/suppliers', 'SupplierController@store');
        $this->router->get('/suppliers/{id}', 'SupplierController@show');
        $this->router->get('/suppliers/{id}/edit', 'SupplierController@edit');
        $this->router->post('/suppliers/{id}', 'SupplierController@update');
        
        // Technicians
        $this->router->get('/technicians', 'TechnicianController@index');
        $this->router->get('/technicians/create', 'TechnicianController@create');
        $this->router->post('/technicians', 'TechnicianController@store');
        $this->router->get('/technicians/{id}/edit', 'TechnicianController@edit');
        $this->router->post('/technicians/{id}', 'TechnicianController@update');
        
        // Work Orders
        $this->router->get('/work-orders', 'WorkOrderController@index');
        $this->router->get('/work-orders/create', 'WorkOrderController@create');
        $this->router->post('/work-orders', 'WorkOrderController@store');
        $this->router->get('/work-orders/{id}', 'WorkOrderController@show');
        $this->router->get('/work-orders/{id}/edit', 'WorkOrderController@edit');
        $this->router->post('/work-orders/{id}', 'WorkOrderController@update');
        
        // Core Liabilities
        $this->router->get('/core-liabilities', 'CoreLiabilityController@index');
        $this->router->post('/core-liabilities/{id}/mark-sent', 'CoreLiabilityController@markSent');
        $this->router->post('/core-liabilities/{id}/record-rebate', 'CoreLiabilityController@recordRebate');
        
        // Reports
        $this->router->get('/reports', 'ReportController@index');
        $this->router->get('/reports/stock', 'ReportController@stock');
        $this->router->get('/reports/movements', 'ReportController@movements');
        $this->router->get('/reports/core-liabilities', 'ReportController@coreLiabilities');
        
        // Audit Log
        $this->router->get('/audit-log', 'AuditController@index');
        
        // API endpoints
        $this->router->get('/api/parts/search', 'Api\PartController@search');
        $this->router->get('/api/work-orders/search', 'Api\WorkOrderController@search');
    }

    public function run(): void
    {
        try {
            $this->router->resolve();
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Exception $e): void
    {
        if ($_ENV['APP_DEBUG'] ?? false) {
            echo '<h1>Error</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            http_response_code(500);
            echo 'An error occurred. Please try again later.';
        }
        
        // Log the error
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
