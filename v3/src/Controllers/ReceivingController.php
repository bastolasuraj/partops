<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Part;
use PartOps\Models\Supplier;
use PartOps\Models\Location;
use PartOps\Models\PartSupplier;
use PartOps\Models\InventoryLevel;
use PartOps\Models\InventoryMove;
use PartOps\Services\Validator;
use PartOps\Services\Database;
use PartOps\Services\AuditLog;
use PartOps\Services\IdempotencyService;

class ReceivingController extends BaseController
{
    public function index(): void
    {
        $parts = Part::getAllWithSummary(true, 50);
        $suppliers = Supplier::getActive();
        $locations = Location::getActive();
        $idempotencyKey = IdempotencyService::generate();

        $this->render('receiving.index', [
            'parts' => $parts,
            'suppliers' => $suppliers,
            'locations' => $locations,
            'idempotencyKey' => $idempotencyKey,
            'pageTitle' => 'Receiving'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('part_id', 'Part is required')
            ->required('supplier_id', 'Supplier is required')
            ->required('location_id', 'Location is required')
            ->required('quantity', 'Quantity is required')
            ->numeric('quantity')
            ->min('quantity', 1, 'Quantity must be at least 1')
            ->required('idempotency_key', 'Idempotency key is required');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['error' => $validator->firstError()], 400);
            } else {
                $this->redirect('/receiving', $validator->firstError(), 'error');
            }
            return;
        }

        if (!IdempotencyService::checkAndMark($input['idempotency_key'])) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Duplicate request detected'], 409);
            } else {
                $this->redirect('/receiving', 'This receiving has already been processed', 'error');
            }
            return;
        }

        $partId = (int)$input['part_id'];
        $supplierId = (int)$input['supplier_id'];
        $locationId = (int)$input['location_id'];
        $quantity = (int)$input['quantity'];
        $price = (float)($input['price'] ?? 0);
        $coreCharge = (float)($input['core_charge'] ?? 0);
        $expectedRebate = (float)($input['expected_rebate'] ?? 0);

        Database::beginTransaction();
        try {
            if (!InventoryLevel::adjustStock($partId, $locationId, $quantity)) {
                throw new \Exception('Failed to adjust stock');
            }

            $moveId = InventoryMove::record([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $quantity,
                'direction' => InventoryMove::DIRECTION_IN,
                'reason' => InventoryMove::REASON_RECEIVE,
                'supplier_id' => $supplierId,
                'price_at_tx' => $price,
                'currency' => 'USD',
                'core_charge_at_tx' => $coreCharge,
                'core_rebate_expected' => $expectedRebate,
                'core_due_state' => $coreCharge > 0 ? InventoryMove::CORE_STATE_DUE : InventoryMove::CORE_STATE_NONE,
                'notes' => $input['notes'] ?? null
            ]);

            $existingPS = PartSupplier::findByPartAndSupplier($partId, $supplierId);
            if ($existingPS) {
                PartSupplier::update($existingPS['id'], [
                    'price' => $price,
                    'core_charge' => $coreCharge,
                    'expected_rebate' => $expectedRebate,
                    'recorded_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                PartSupplier::create([
                    'part_id' => $partId,
                    'supplier_id' => $supplierId,
                    'sku' => $input['sku'] ?? null,
                    'price' => $price,
                    'currency' => 'USD',
                    'core_charge' => $coreCharge,
                    'expected_rebate' => $expectedRebate,
                    'recorded_at' => date('Y-m-d H:i:s')
                ]);
            }

            $correlationId = AuditLog::generateCorrelationId();
            
            AuditLog::log('receive', 'inventory_move', $moveId, [
                'part_id' => $partId,
                'supplier_id' => $supplierId,
                'location_id' => $locationId,
                'quantity' => $quantity,
                'price' => $price,
                'core_charge' => $coreCharge,
                'expected_rebate' => $expectedRebate
            ], $correlationId);
            
            AuditLog::log('stock_increase', 'inventory_level', null, [
                'part_id' => $partId,
                'location_id' => $locationId,
                'quantity_added' => $quantity
            ], $correlationId);
            
            if ($coreCharge > 0) {
                AuditLog::log('core_liability_created', 'inventory_move', $moveId, [
                    'part_id' => $partId,
                    'core_charge' => $coreCharge,
                    'expected_rebate' => $expectedRebate,
                    'state' => 'due'
                ], $correlationId);
            }
            
            AuditLog::log('supplier_pricing_updated', 'part_supplier', null, [
                'part_id' => $partId,
                'supplier_id' => $supplierId,
                'price' => $price,
                'core_charge' => $coreCharge
            ], $correlationId);

            Database::commit();

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => "Received $quantity units successfully"]);
            } else {
                $this->redirect('/receiving', "Received $quantity units successfully");
            }
        } catch (\Exception $e) {
            Database::rollback();
            error_log('Receiving error: ' . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->json(['error' => 'Failed to process receiving'], 500);
            } else {
                $this->redirect('/receiving', 'Failed to process receiving', 'error');
            }
        }
    }
}
