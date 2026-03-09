<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Models\WorkOrder;

class WorkOrderController extends BaseController
{
    private WorkOrder $workOrder;
    
    public function __construct()
    {
        parent::__construct();
        $this->workOrder = new WorkOrder();
    }
    
    public function index(): void
    {
        $workOrders = $this->workOrder->getAllWithDetails();
        Response::success($workOrders);
    }
    
    public function show(int $id): void
    {
        $workOrder = $this->workOrder->findWithDetails($id);
        
        if (!$workOrder) {
            Response::notFound('Work Order not found');
        }
        
        Response::success($workOrder);
    }
    
    public function store(): void
    {
        $data = $this->request->all();
        
        // Auto-generate WO number if not provided
        if (empty($data['wo_number'])) {
            $data['wo_number'] = $this->workOrder->generateWoNumber();
        }
        
        // Check for duplicate wo_number
        $existing = $this->workOrder->findBy('wo_number', $data['wo_number']);
        if ($existing) {
            Response::error('Work Order number already exists', 422);
        }
        
        $workOrder = $this->workOrder->create($data);
        $workOrder = $this->workOrder->findWithDetails($workOrder['id']);
        Response::created($workOrder, 'Work Order created successfully');
    }
    
    public function update(int $id): void
    {
        $existing = $this->workOrder->find($id);
        if (!$existing) {
            Response::notFound('Work Order not found');
        }
        
        $data = $this->request->all();
        $this->workOrder->update($id, $data);
        $workOrder = $this->workOrder->findWithDetails($id);
        Response::success($workOrder, 'Work Order updated successfully');
    }
    
    public function destroy(int $id): void
    {
        $existing = $this->workOrder->find($id);
        if (!$existing) {
            Response::notFound('Work Order not found');
        }
        
        $this->workOrder->delete($id);
        Response::success(null, 'Work Order deleted successfully');
    }
    
    public function getOpen(): void
    {
        $workOrders = $this->workOrder->getOpen();
        Response::success($workOrders);
    }
}
