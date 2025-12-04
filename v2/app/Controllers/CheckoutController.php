<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Part;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Models\Location;
use App\Models\InventoryLevel;
use App\Models\InventoryMove;
use App\Models\AuditLog;

/**
 * Checkout Controller - Parts checkout to work orders
 * This file should be moved to: app/Controllers/CheckoutController.php
 */
class CheckoutController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $moveModel = new InventoryMove();
        $recentCheckouts = $moveModel->getByReason('checkout', 30);

        $this->renderWithLayout('checkout/index', [
            'title' => 'Checkout',
            'recentCheckouts' => $recentCheckouts,
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();

        $partModel = new Part();
        $techModel = new Technician();
        $woModel = new WorkOrder();

        $this->renderWithLayout('checkout/create', [
            'title' => 'Checkout Parts',
            'parts' => $partModel->getAllWithStock(['is_active' => 1]),
            'technicians' => $techModel->getActive(),
            'workOrders' => $woModel->getAllWithTechnician(),
            'idempotencyKey' => $this->generateIdempotencyKey(),
        ]);
    }

    public function store(): void
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
            'technician_id' => 'required|numeric',
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
            $this->redirect('/checkout');
            return;
        }

        $inventoryModel = new InventoryLevel();
        $qty = abs((int)$data['qty']);

        // Check available stock
        $stock = $inventoryModel->query(
            "SELECT * FROM inventory_levels WHERE part_id = :part_id AND location_id = :location_id",
            ['part_id' => $data['part_id'], 'location_id' => $data['location_id']]
        );

        if (empty($stock) || ($stock[0]['on_hand'] - $stock[0]['reserved']) < $qty) {
            $this->setFlash('error', 'Insufficient stock available at this location.');
            $this->back();
            return;
        }

        try {
            $inventoryModel->beginTransaction();

            // Reduce stock
            $inventoryModel->adjustStock(
                (int)$data['part_id'],
                (int)$data['location_id'],
                -$qty
            );

            // Record the checkout
            $moveId = $moveModel->recordCheckout([
                'part_id' => (int)$data['part_id'],
                'location_id' => (int)$data['location_id'],
                'qty' => $qty,
                'work_order_id' => (int)$data['work_order_id'],
                'technician_id' => (int)$data['technician_id'],
                'idempotency_key' => $idempotencyKey,
                'user_id' => $this->user['id'] ?? null,
                'notes' => $this->request->post('notes'),
            ]);

            // Audit log
            $auditLog = new AuditLog();
            $auditLog->log('checkout', 'inventory_move', $moveId, null, [
                'part_id' => $data['part_id'],
                'qty' => $qty,
                'work_order_id' => $data['work_order_id'],
                'technician_id' => $data['technician_id'],
            ]);

            $inventoryModel->commit();

            $this->setFlash('success', "Successfully checked out {$qty} parts.");
            $this->redirect('/checkout');

        } catch (\Exception $e) {
            $inventoryModel->rollback();
            $this->setFlash('error', 'Failed to process checkout: ' . $e->getMessage());
            $this->back();
        }
    }
}
