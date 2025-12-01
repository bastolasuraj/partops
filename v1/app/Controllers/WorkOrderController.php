<?php
/**
 * Work Order Controller
 * 
 * Handle work orders CRUD operations
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;
use PartOps\Models\WorkOrder;

class WorkOrderController extends Controller
{
    private WorkOrder $workOrderModel;

    public function __construct()
    {
        $this->workOrderModel = new WorkOrder();
    }

    public function index(): void
    {
        $this->requireAuth();
        $workOrders = $this->workOrderModel->all();
        $this->view('work-orders.index', [
            'title' => 'Work Orders',
            'workOrders' => $workOrders
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $workOrder = $this->workOrderModel->find((int)$id);
        
        if (!$workOrder) {
            http_response_code(404);
            echo "Work order not found";
            return;
        }
        
        $this->view('work-orders.show', [
            'title' => 'Work Order Details',
            'workOrder' => $workOrder
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        $this->view('work-orders.create', [
            'title' => 'Create Work Order',
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        // TODO: Implement work order creation
        $this->json(['message' => 'Work order creation not yet implemented'], 501);
    }
}
