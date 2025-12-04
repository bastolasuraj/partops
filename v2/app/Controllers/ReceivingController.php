<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Models\InventoryLevel;
use App\Models\InventoryMove;
use App\Models\CoreLiability;
use App\Models\AuditLog;

/**
 * Receiving Controller - New Parts & Work Order Returns
 * This file should be moved to: app/Controllers/ReceivingController.php
 */
class ReceivingController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $moveModel = new InventoryMove();
        
        // Get recent receiving transactions
        $recentReceives = $moveModel->getByReason('receive_new', 20);
        $recentWoReturns = $moveModel->getByReason('receive_wo_return', 20);

        $this->renderWithLayout('receiving/index', [
            'title' => 'Receiving',
            'recentReceives' => $recentReceives,
            'recentWoReturns' => $recentWoReturns,
        ]);
    }

    /**
     * Show form for receiving new parts
     */
    public function createNew(): void
    {
        $this->requireAuth();

        $partModel = new Part();
        $supplierModel = new Supplier();
        $locationModel = new Location();

        $this->renderWithLayout('receiving/new', [
            'title' => 'Receive New Parts',
            'parts' => $partModel->getAllWithStock(['is_active' => 1]),
            'suppliers' => $supplierModel->getActive(),
            'locations' => $locationModel->getActive(),
            'idempotencyKey' => $this->generateIdempotencyKey(),
        ]);
    }

    /**
     * Process new parts receiving
     */
    public function storeNew(): void
    {
        $this->requireAuth();

        if (!$this->validateCsrf()) {
            $this->back();
            return;
        }

        // Validate input
        $validation = $this->validate([
            'part_id' => 'required|numeric',
            'supplier_id' => 'required|numeric',
            'location_id' => 'required|numeric',
            'qty' => 'required|numeric',
        ]);

        if (!$validation['valid']) {
            $this->setFlash('error', 'Please fill in all required fields.');
            $this->back();
            return;
        }

        $data = $validation['data'];
        $idempotencyKey = $this->request->post('idempotency_key');

        // Check idempotency
        $moveModel = new InventoryMove();
        if ($idempotencyKey && $moveModel->idempotencyKeyExists($idempotencyKey)) {
            $this->setFlash('warning', 'This transaction has already been processed.');
            $this->redirect('/receiving');
            return;
        }

        $inventoryModel = new InventoryLevel();

        try {
            $inventoryModel->beginTransaction();

            // Adjust stock
            $qty = abs((int)$data['qty']);
            $inventoryModel->adjustStock(
                (int)$data['part_id'],
                (int)$data['location_id'],
                $qty
            );

            // Record the movement
            $moveId = $moveModel->recordReceiveNew([
                'part_id' => (int)$data['part_id'],
                'part_number_id' => $this->request->post('part_number_id') ?: null,
                'location_id' => (int)$data['location_id'],
                'qty' => $qty,
                'supplier_id' => (int)$data['supplier_id'],
                'supplier_sku' => $this->request->post('supplier_sku'),
                'po_number' => $this->request->post('po_number'),
                'price_at_tx' => $this->request->post('price') ?: null,
                'core_charge_at_tx' => $this->request->post('core_charge') ?: null,
                'core_rebate_expected' => $this->request->post('expected_rebate') ?: null,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $this->user['id'] ?? null,
                'notes' => $this->request->post('notes'),
            ]);

            // Create core liability if core charge exists
            $coreCharge = (float)($this->request->post('core_charge') ?? 0);
            $expectedRebate = (float)($this->request->post('expected_rebate') ?? 0);
            
            if ($coreCharge > 0) {
                $coreModel = new CoreLiability();
                $coreModel->create([
                    'receive_move_id' => $moveId,
                    'part_id' => (int)$data['part_id'],
                    'part_number_id' => $this->request->post('part_number_id') ?: null,
                    'supplier_id' => (int)$data['supplier_id'],
                    'qty_due' => $qty,
                    'core_charge' => $coreCharge,
                    'expected_rebate' => $expectedRebate,
                    'due_date' => date('Y-m-d', strtotime('+30 days')),
                    'status' => 'pending',
                ]);
            }

            // Audit log
            $auditLog = new AuditLog();
            $auditLog->log('receive_new', 'inventory_move', $moveId, null, [
                'part_id' => $data['part_id'],
                'qty' => $qty,
                'supplier_id' => $data['supplier_id'],
            ]);

            $inventoryModel->commit();

            $this->setFlash('success', "Successfully received {$qty} parts.");
            $this->redirect('/receiving');

        } catch (\Exception $e) {
            $inventoryModel->rollback();
            $this->setFlash('error', 'Failed to process receiving: ' . $e->getMessage());
            $this->back();
        }
    }

    /**
     * Show form for work order returns
     */
    public function createWoReturn(): void
    {
        $this->requireAuth();

        $partModel = new Part();
        $techModel = new Technician();
        $woModel = new WorkOrder();
        $locationModel = new Location();

        $this->renderWithLayout('receiving/wo_return', [
            'title' => 'Work Order Return',
            'parts' => $partModel->getAllWithStock(['is_active' => 1]),
            'technicians' => $techModel->getActive(),
            'workOrders' => $woModel->getAllWithTechnician(['status' => 'open']),
            'locations' => $locationModel->getActive(),
            'idempotencyKey' => $this->generateIdempotencyKey(),
        ]);
    }

    /**
     * Process work order return
     */
    public function storeWoReturn(): void
    {
        $this->requireAuth();

        if (!$this->validateCsrf()) {
            $this->back();
            return;
        }

        $validation = $this->validate([
            'part_id' => 'required|numeric',
            'location_id' => 'required|numeric',
            'qty' => 'required|numeric',
            'work_order_id' => 'required|numeric',
        ]);

        if (!$validation['valid']) {
            $this->setFlash('error', 'Please fill in all required fields.');
            $this->back();
            return;
        }

        $data = $validation['data'];
        $idempotencyKey = $this->request->post('idempotency_key');

        $moveModel = new InventoryMove();
        if ($idempotencyKey && $moveModel->idempotencyKeyExists($idempotencyKey)) {
            $this->setFlash('warning', 'This transaction has already been processed.');
            $this->redirect('/receiving');
            return;
        }

        $inventoryModel = new InventoryLevel();

        try {
            $inventoryModel->beginTransaction();

            $qty = abs((int)$data['qty']);
            $inventoryModel->adjustStock(
                (int)$data['part_id'],
                (int)$data['location_id'],
                $qty
            );

            $moveId = $moveModel->recordWoReturn([
                'part_id' => (int)$data['part_id'],
                'location_id' => (int)$data['location_id'],
                'qty' => $qty,
                'work_order_id' => (int)$data['work_order_id'],
                'technician_id' => $this->request->post('technician_id') ?: null,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $this->user['id'] ?? null,
                'notes' => $this->request->post('notes'),
            ]);

            $auditLog = new AuditLog();
            $auditLog->log('receive_wo_return', 'inventory_move', $moveId, null, [
                'part_id' => $data['part_id'],
                'qty' => $qty,
                'work_order_id' => $data['work_order_id'],
            ]);

            $inventoryModel->commit();

            $this->setFlash('success', "Successfully returned {$qty} parts from work order.");
            $this->redirect('/receiving');

        } catch (\Exception $e) {
            $inventoryModel->rollback();
            $this->setFlash('error', 'Failed to process return: ' . $e->getMessage());
            $this->back();
        }
    }
}
