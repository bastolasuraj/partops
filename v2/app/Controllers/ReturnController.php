<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\InventoryLevel;
use App\Models\InventoryMove;
use App\Models\CoreLiability;
use App\Models\AuditLog;

/**
 * Return Controller - Standard Returns & Core Returns
 * This file should be moved to: app/Controllers/ReturnController.php
 */
class ReturnController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $moveModel = new InventoryMove();
        
        $standardReturns = $moveModel->getByReason('return_standard', 20);
        $coreReturns = $moveModel->getByReason('return_core', 20);

        $this->renderWithLayout('returns/index', [
            'title' => 'Returns',
            'standardReturns' => $standardReturns,
            'coreReturns' => $coreReturns,
        ]);
    }

    /**
     * Show standard return form
     */
    public function createStandard(): void
    {
        $this->requireAuth();

        $partModel = new Part();
        $supplierModel = new Supplier();
        $locationModel = new Location();

        $this->renderWithLayout('returns/standard', [
            'title' => 'Standard Return',
            'parts' => $partModel->getAllWithStock(['is_active' => 1]),
            'suppliers' => $supplierModel->getActive(),
            'locations' => $locationModel->getActive(),
            'idempotencyKey' => $this->generateIdempotencyKey(),
        ]);
    }

    /**
     * Process standard return
     */
    public function storeStandard(): void
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
            'notes' => 'required',
        ]);

        if (!$validation['valid']) {
            $this->setFlash('error', 'Please fill in all required fields including reason.');
            $this->back();
            return;
        }

        $data = $validation['data'];
        $idempotencyKey = $this->request->post('idempotency_key');

        $moveModel = new InventoryMove();
        if ($idempotencyKey && $moveModel->idempotencyKeyExists($idempotencyKey)) {
            $this->setFlash('warning', 'This transaction has already been processed.');
            $this->redirect('/returns');
            return;
        }

        $inventoryModel = new InventoryLevel();
        $qty = abs((int)$data['qty']);

        // Check if there's stock to return
        $stock = $inventoryModel->query(
            "SELECT * FROM inventory_levels WHERE part_id = :part_id AND location_id = :location_id",
            ['part_id' => $data['part_id'], 'location_id' => $data['location_id']]
        );

        if (empty($stock) || $stock[0]['on_hand'] < $qty) {
            $this->setFlash('error', 'Cannot return more than available stock at this location.');
            $this->back();
            return;
        }

        try {
            $inventoryModel->beginTransaction();

            // Remove from stock (standard return removes from inventory)
            $inventoryModel->adjustStock(
                (int)$data['part_id'],
                (int)$data['location_id'],
                -$qty
            );

            $moveId = $moveModel->recordStandardReturn([
                'part_id' => (int)$data['part_id'],
                'location_id' => (int)$data['location_id'],
                'qty' => -$qty, // Negative because it's leaving inventory
                'supplier_id' => $this->request->post('supplier_id') ?: null,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $this->user['id'] ?? null,
                'notes' => $data['notes'],
            ]);

            $auditLog = new AuditLog();
            $auditLog->log('return_standard', 'inventory_move', $moveId, null, [
                'part_id' => $data['part_id'],
                'qty' => $qty,
                'reason' => $data['notes'],
            ]);

            $inventoryModel->commit();

            $this->setFlash('success', "Successfully processed return of {$qty} parts.");
            $this->redirect('/returns');

        } catch (\Exception $e) {
            $inventoryModel->rollback();
            $this->setFlash('error', 'Failed to process return: ' . $e->getMessage());
            $this->back();
        }
    }

    /**
     * Show core return form
     */
    public function createCore(): void
    {
        $this->requireAuth();

        $partModel = new Part();
        $supplierModel = new Supplier();

        $this->renderWithLayout('returns/core', [
            'title' => 'Core Return',
            'parts' => $partModel->getAllWithStock(['is_active' => 1]),
            'suppliers' => $supplierModel->getActive(),
            'idempotencyKey' => $this->generateIdempotencyKey(),
        ]);
    }

    /**
     * Process core return
     */
    public function storeCore(): void
    {
        $this->requireAuth();

        if (!$this->validateCsrf()) {
            $this->back();
            return;
        }

        $validation = $this->validate([
            'part_id' => 'required|numeric',
            'supplier_id' => 'required|numeric',
            'qty' => 'required|numeric',
            'core_charge' => 'required|numeric',
            'expected_rebate' => 'required|numeric',
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
            $this->redirect('/returns');
            return;
        }

        $qty = abs((int)$data['qty']);
        $coreCharge = (float)$data['core_charge'];
        $expectedRebate = (float)$data['expected_rebate'];
        $coreDueState = $this->request->post('core_due_state', 'due');

        try {
            $moveModel->beginTransaction();

            // Record the core return movement
            $moveId = $moveModel->recordCoreReturn([
                'part_id' => (int)$data['part_id'],
                'location_id' => 1, // Core returns don't affect location stock
                'qty' => $qty,
                'supplier_id' => (int)$data['supplier_id'],
                'core_charge_at_tx' => $coreCharge,
                'core_rebate_expected' => $expectedRebate,
                'core_due_state' => $coreDueState,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $this->user['id'] ?? null,
                'notes' => $this->request->post('notes'),
            ]);

            // Create or update core liability
            $coreModel = new CoreLiability();
            $coreModel->create([
                'receive_move_id' => $moveId,
                'part_id' => (int)$data['part_id'],
                'supplier_id' => (int)$data['supplier_id'],
                'qty_due' => $qty,
                'core_charge' => $coreCharge,
                'expected_rebate' => $expectedRebate,
                'due_date' => $this->request->post('due_date') ?: date('Y-m-d', strtotime('+30 days')),
                'status' => $coreDueState === 'sent' ? 'sent' : 'pending',
                'rma_number' => $this->request->post('rma_number'),
            ]);

            $auditLog = new AuditLog();
            $auditLog->log('return_core', 'inventory_move', $moveId, null, [
                'part_id' => $data['part_id'],
                'qty' => $qty,
                'core_charge' => $coreCharge,
                'expected_rebate' => $expectedRebate,
            ]);

            $moveModel->commit();

            $totalValue = $qty * $expectedRebate;
            $this->setFlash('success', "Core return recorded. Expected rebate: \${$totalValue}");
            $this->redirect('/core-liabilities');

        } catch (\Exception $e) {
            $moveModel->rollback();
            $this->setFlash('error', 'Failed to process core return: ' . $e->getMessage());
            $this->back();
        }
    }
}
