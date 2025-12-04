<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Part;
use PartOps\Models\Location;
use PartOps\Models\InventoryLevel;
use PartOps\Models\InventoryMove;
use PartOps\Services\Validator;
use PartOps\Services\Database;
use PartOps\Services\AuditLog;
use PartOps\Services\IdempotencyService;

class ReturnsController extends BaseController
{
    public function index(): void
    {
        $parts = Part::getAllWithSummary(true, 50);
        $locations = Location::getActive();
        $pendingCores = InventoryMove::getPendingCoreReturns();
        $idempotencyKey = IdempotencyService::generate();

        $this->render('returns.index', [
            'parts' => $parts,
            'locations' => $locations,
            'pendingCores' => $pendingCores,
            'idempotencyKey' => $idempotencyKey,
            'pageTitle' => 'Returns & Core'
        ]);
    }

    public function store(): void
    {
        $input = $this->getInput();
        
        $validator = new Validator($input);
        $validator
            ->required('part_id', 'Part is required')
            ->required('location_id', 'Location is required')
            ->required('quantity', 'Quantity is required')
            ->required('return_type', 'Return type is required')
            ->in('return_type', ['standard', 'core'])
            ->required('idempotency_key', 'Idempotency key is required');

        if ($validator->fails()) {
            if ($this->isAjax()) {
                $this->json(['error' => $validator->firstError()], 400);
            } else {
                $this->redirect('/returns', $validator->firstError(), 'error');
            }
            return;
        }

        if (!IdempotencyService::checkAndMark($input['idempotency_key'])) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Duplicate request detected'], 409);
            } else {
                $this->redirect('/returns', 'This return has already been processed', 'error');
            }
            return;
        }

        $partId = (int)$input['part_id'];
        $locationId = (int)$input['location_id'];
        $quantity = (int)$input['quantity'];
        $returnType = $input['return_type'];
        $coreChargeAtTx = (float)($input['core_charge'] ?? 0);
        $rebateExpected = (float)($input['rebate_expected'] ?? 0);
        $rebateReceived = (float)($input['rebate_received'] ?? 0);
        $coreDueState = $input['core_due_state'] ?? InventoryMove::CORE_STATE_NONE;

        Database::beginTransaction();
        try {
            $reason = $returnType === 'core' ? InventoryMove::REASON_CORE_RETURN : InventoryMove::REASON_RETURN;
            
            if ($returnType === 'standard') {
                if (!InventoryLevel::adjustStock($partId, $locationId, $quantity)) {
                    throw new \Exception('Failed to adjust stock');
                }
            }

            $moveId = InventoryMove::record([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $quantity,
                'direction' => InventoryMove::DIRECTION_IN,
                'reason' => $reason,
                'core_charge_at_tx' => $coreChargeAtTx,
                'core_rebate_expected' => $rebateExpected,
                'core_rebate_received' => $rebateReceived,
                'core_due_state' => $coreDueState,
                'notes' => $input['notes'] ?? null
            ]);

            AuditLog::log('return', 'inventory', $moveId, [
                'part_id' => $partId,
                'return_type' => $returnType,
                'quantity' => $quantity,
                'core_due_state' => $coreDueState
            ]);

            Database::commit();

            $message = $returnType === 'core' 
                ? "Core return recorded for $quantity units" 
                : "Returned $quantity units successfully";

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => $message]);
            } else {
                $this->redirect('/returns', $message);
            }
        } catch (\Exception $e) {
            Database::rollback();
            error_log('Return error: ' . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->json(['error' => 'Failed to process return'], 500);
            } else {
                $this->redirect('/returns', 'Failed to process return', 'error');
            }
        }
    }

    public function updateCoreStatus(): void
    {
        $input = $this->getInput();
        
        $moveId = (int)($input['move_id'] ?? 0);
        $newState = $input['core_due_state'] ?? '';
        $rebateReceived = (float)($input['rebate_received'] ?? 0);

        if (!$moveId || !$newState) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }

        $move = InventoryMove::find($moveId);
        if (!$move) {
            $this->json(['error' => 'Move not found'], 404);
            return;
        }

        InventoryMove::update($moveId, [
            'core_due_state' => $newState,
            'core_rebate_received' => $rebateReceived
        ]);

        AuditLog::log('update_core_status', 'inventory_move', $moveId, [
            'old_state' => $move['core_due_state'],
            'new_state' => $newState,
            'rebate_received' => $rebateReceived
        ]);

        $this->json(['success' => true]);
    }
}
