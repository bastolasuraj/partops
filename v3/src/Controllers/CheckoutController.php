<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Part;
use PartOps\Models\Technician;
use PartOps\Models\Location;
use PartOps\Models\WorkOrder;
use PartOps\Models\InventoryLevel;
use PartOps\Models\InventoryMove;
use PartOps\Services\Validator;
use PartOps\Services\Database;
use PartOps\Services\AuditLog;
use PartOps\Services\IdempotencyService;

class CheckoutController extends BaseController
{
    public function index(): void
    {
        $parts = Part::getAllWithSummary(true, 50);
        $technicians = Technician::getActive();
        $locations = Location::getActive();
        $idempotencyKey = IdempotencyService::generate();

        $this->render('checkout.index', [
            'parts' => $parts,
            'technicians' => $technicians,
            'locations' => $locations,
            'idempotencyKey' => $idempotencyKey,
            'pageTitle' => 'Checkout to Technician'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('part_id', 'Part is required')
            ->required('technician_id', 'Technician is required')
            ->required('location_id', 'Location is required')
            ->required('quantity', 'Quantity is required')
            ->numeric('quantity')
            ->min('quantity', 1, 'Quantity must be at least 1')
            ->required('idempotency_key', 'Idempotency key is required');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['error' => $validator->firstError()], 400);
            } else {
                $this->redirect('/checkout', $validator->firstError(), 'error');
            }
            return;
        }

        if (!IdempotencyService::checkAndMark($input['idempotency_key'])) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Duplicate request detected'], 409);
            } else {
                $this->redirect('/checkout', 'This checkout has already been processed', 'error');
            }
            return;
        }

        $partId = (int)$input['part_id'];
        $technicianId = (int)$input['technician_id'];
        $locationId = (int)$input['location_id'];
        $quantity = (int)$input['quantity'];
        $workOrderRef = trim($input['work_order'] ?? '');
        $unitNumber = trim($input['unit_number'] ?? '');

        $available = InventoryLevel::getAvailable($partId, $locationId);
        if ($available < $quantity) {
            if ($this->isAjax()) {
                $this->json(['error' => "Only $available units available at this location"], 400);
            } else {
                $this->redirect('/checkout', "Only $available units available at this location", 'error');
            }
            return;
        }

        Database::beginTransaction();
        try {
            if (!InventoryLevel::adjustStock($partId, $locationId, -$quantity)) {
                throw new \Exception('Failed to adjust stock');
            }

            $workOrderId = null;
            if ($workOrderRef) {
                $workOrderId = WorkOrder::getOrCreate($workOrderRef, $unitNumber);
            }

            $moveId = InventoryMove::record([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $quantity,
                'direction' => InventoryMove::DIRECTION_OUT,
                'reason' => InventoryMove::REASON_CHECKOUT,
                'technician_id' => $technicianId,
                'work_order_id' => $workOrderId,
                'notes' => $input['notes'] ?? null
            ]);

            $correlationId = AuditLog::generateCorrelationId();
            
            AuditLog::log('checkout', 'inventory_move', $moveId, [
                'part_id' => $partId,
                'technician_id' => $technicianId,
                'location_id' => $locationId,
                'quantity' => $quantity,
                'work_order_ref' => $workOrderRef,
                'work_order_id' => $workOrderId,
                'unit_number' => $unitNumber
            ], $correlationId);
            
            AuditLog::log('stock_decrease', 'inventory_level', null, [
                'part_id' => $partId,
                'location_id' => $locationId,
                'quantity_removed' => $quantity
            ], $correlationId);
            
            if ($workOrderId) {
                AuditLog::log('work_order_part_linked', 'work_order', $workOrderId, [
                    'part_id' => $partId,
                    'technician_id' => $technicianId,
                    'quantity' => $quantity
                ], $correlationId);
            }

            Database::commit();

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => "Checked out $quantity units successfully"]);
            } else {
                $this->redirect('/checkout', "Checked out $quantity units successfully");
            }
        } catch (\Exception $e) {
            Database::rollback();
            error_log('Checkout error: ' . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->json(['error' => 'Failed to process checkout'], 500);
            } else {
                $this->redirect('/checkout', 'Failed to process checkout', 'error');
            }
        }
    }
}
