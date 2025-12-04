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

        $externalRef = trim($this->post('external_ref', ''));
        if (empty($externalRef)) {
            $this->json(['error' => 'External Reference is required'], 400);
        }

        $data = [
            'external_ref' => $externalRef,
            'vehicle_ref' => trim($this->post('vehicle_ref', '')),
            'status' => 'open',
            'opened_at' => date('Y-m-d H:i:s')
        ];

        try {
            $id = $this->workOrderModel->create($data);
            $this->redirect('/work-orders/' . $id);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->json(['error' => 'Work Order ID already exists'], 400);
            }
            throw $e;
        }
    }

    public function updateStatus(string $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $status = $this->post('status');
        $validStatuses = ['open', 'in_progress', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            $this->json(['error' => 'Invalid status'], 400);
        }

        $data = ['status' => $status];
        if (in_array($status, ['completed', 'cancelled'])) {
            $data['closed_at'] = date('Y-m-d H:i:s');
        } else {
            $data['closed_at'] = null;
        }

        $this->workOrderModel->update((int)$id, $data);
        $this->redirect('/work-orders/' . $id);
    }
}
