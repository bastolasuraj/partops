<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Core\Database;
use App\Models\Part;
use App\Models\InventoryTransaction;
use App\Models\InventoryLocationLevel;
use App\Models\WorkOrder;
use App\Models\Unit;
use App\Models\Technician;
use App\Models\Setting;

class InventoryController extends BaseController
{
    private Part $part;
    private InventoryTransaction $transaction;
    private InventoryLocationLevel $locationLevel;
    private WorkOrder $workOrder;
    private Unit $unit;
    private Technician $technician;
    private Setting $settings;
    
    public function __construct()
    {
        parent::__construct();
        $this->part = new Part();
        $this->transaction = new InventoryTransaction();
        $this->locationLevel = new InventoryLocationLevel();
        $this->workOrder = new WorkOrder();
        $this->unit = new Unit();
        $this->technician = new Technician();
        $this->settings = new Setting();
    }
    
    /**
     * Process incoming inventory (receiving)
     */
    public function incoming(): void
    {
        $data = $this->validate([
            'part_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $part = $this->part->find((int)$data['part_id']);
        if (!$part) {
            Response::notFound('Part not found');
        }

        // Check if this is a return (has reference_type and reference_id)
        $referenceType = $this->request->get('reference_type');
        $referenceId = $this->request->get('reference_id');

        // If it's a return, validate against what was taken (unless contingency mode is enabled)
        if ($referenceType && $referenceId) {
            $allowUntracked = (bool)$this->settings->get('allow_untracked_returns', false);
            if (!$allowUntracked) {
                $this->validateReturn((int)$data['part_id'], (int)$data['quantity'], (string)$referenceType, (int)$referenceId);
            }
        }

        $partUnit = $this->normalizeUnitOfMeasure($part['unit_of_measure'] ?? 'each');
        $incomingUnit = $this->normalizeUnitOfMeasure($this->request->get('unit_of_measure', $partUnit));
        if ($incomingUnit !== $partUnit) {
            Response::error("Unit mismatch for {$part['fowler_part_number']}: expected {$partUnit}, got {$incomingUnit}", 422);
        }

        $supplierId = $this->request->get('supplier_id');
        $unitPrice = $this->request->get('unit_price', null);
        $notes = $this->request->get('notes');

        if (!$referenceType && !$referenceId) {
            if ($unitPrice === null || $unitPrice === '' || !is_numeric($unitPrice) || (float)$unitPrice <= 0) {
                Response::error('unit_price is required for vendor incoming', 422);
            }
        }
        $unitPrice = is_numeric($unitPrice) ? (float)$unitPrice : 0.0;

        // Core tracking (per transaction, not updating part master)
        $hasCore = $this->request->get('has_core', false);
        $coreCost = $this->request->get('core_cost', 0);
        $coreRebateExpected = $this->request->get('core_rebate_expected', 0);
        $coreRebateReceived = $this->request->get('core_rebate_received', 0);
        $locationPayload = $this->buildLocationPayload();
        $normalizedLocation = $this->locationLevel->normalizeLocationPayload($locationPayload, $part);

        // Get logged-in user info (use display name)
        $createdBy = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? null;

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Adjust stock and record transaction atomically
            $newStock = $this->part->adjustStock((int)$data['part_id'], (int)$data['quantity']);
            $this->locationLevel->addStock((int)$data['part_id'], (int)$data['quantity'], $normalizedLocation, $part);

            if ($referenceType && $referenceId) {
                $returnUnitPrice = $this->part->getReferenceOutstandingIssueUnitPrice(
                    (int)$data['part_id'],
                    (string)$referenceType,
                    (int)$referenceId,
                    (int)$data['quantity']
                );

                $this->transaction->recordReturn(
                    (int)$data['part_id'],
                    (int)$data['quantity'],
                    (string)$referenceType,
                    (int)$referenceId,
                    $notes,
                    $createdBy,
                    $returnUnitPrice,
                    null,
                    $normalizedLocation
                );
            } else {
                $this->transaction->recordIncoming(
                    (int)$data['part_id'],
                    (int)$data['quantity'],
                    is_numeric($supplierId) ? (int)$supplierId : null,
                    $unitPrice,
                    $notes,
                    $createdBy,
                    (bool)$hasCore,
                    (float)$coreCost,
                    (float)$coreRebateExpected,
                    (float)$coreRebateReceived,
                    'vendor',
                    $normalizedLocation
                );
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('Incoming processing failed', 500, $e->getMessage());
        }

        $this->part->syncUnitPriceFromFifo((int)$data['part_id']);

        Response::success([
            'part_id' => $data['part_id'],
            'quantity_added' => $data['quantity'],
            'new_stock' => $newStock,
            'location' => $normalizedLocation['location_display'] ?? null,
        ], 'Incoming inventory processed successfully');
    }
    
    /**
     * Process checkout (parts going out)
     */
    public function checkout(): void
    {
        $data = $this->validate([
            'items' => 'required',
            'reference_type' => 'required|string',
            'reference_id' => 'required|integer',
        ]);
        
        $items = $this->request->get('items');
        $refType = $data['reference_type']; // work_order, unit, technician
        $refId = $data['reference_id'];
        $notes = $this->request->get('notes');
        
        if (!is_array($items) || empty($items)) {
            Response::error('Items must be a non-empty array', 422);
        }
        
        // Validate reference exists
        $this->validateReference($refType, $refId);
        
        // Validate all items have sufficient stock
        $errors = [];
        foreach ($items as $index => $item) {
            if (empty($item['part_id']) || empty($item['quantity'])) {
                $errors[] = "Item {$index}: part_id and quantity are required";
                continue;
            }

            $partId = (int)$item['part_id'];
            $requestedQty = (int)$item['quantity'];
            if ($requestedQty <= 0) {
                $errors[] = "Item {$index}: quantity must be at least 1";
                continue;
            }

            $part = $this->part->find($partId);
            if (!$part) {
                $errors[] = "Item {$index}: part not found";
                continue;
            }

            $currentStock = $this->part->getStock($partId);
            if ($currentStock < $requestedQty) {
                $partName = $part['fowler_part_number'] ?? "ID:{$partId}";
                $errors[] = "Insufficient stock for {$partName}: requested {$requestedQty}, available {$currentStock}";
                continue;
            }

            $requestedLocationKey = $this->locationLevel->resolveRequestedLocationKey((array)$item, $part);
            if ($requestedLocationKey !== null) {
                $locationStock = $this->locationLevel->getLocationStock($partId, $requestedLocationKey, $part);
                if ($locationStock < $requestedQty) {
                    $partName = $part['fowler_part_number'] ?? "ID:{$partId}";
                    $errors[] = "Insufficient stock at selected location for {$partName}: requested {$requestedQty}, available {$locationStock}";
                }
            }
        }
        
        if (!empty($errors)) {
            Response::error('Stock validation failed', 422, $errors);
        }
        
        // Get logged-in user info (use display name)
        $createdBy = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? null;

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Re-validate stock inside the transaction with row locking to prevent oversell.
            foreach ($items as $item) {
                $partId = (int)$item['part_id'];
                $requestedQty = (int)$item['quantity'];

                $lockedRow = Database::query(
                    "SELECT quantity FROM inventory_levels WHERE part_id = ? FOR UPDATE",
                    [$partId]
                )->fetch();
                $lockedStock = $lockedRow ? (int)$lockedRow['quantity'] : 0;

                if ($lockedStock < $requestedQty) {
                    $db->rollBack();
                    $part = $this->part->find($partId);
                    $partName = $part ? ($part['fowler_part_number'] ?? "ID:{$partId}") : "ID:{$partId}";
                    Response::error("Insufficient stock for {$partName}: requested {$requestedQty}, available {$lockedStock}", 422);
                }
            }

            // Process checkout
            $results = [];
            $checkoutAt = date('Y-m-d H:i:s');
            foreach ($items as $item) {
                $partId = (int)$item['part_id'];
                $requestedQty = (int)$item['quantity'];
                $part = $this->part->find($partId);
                if (!$part) {
                    throw new \RuntimeException("Part not found: {$partId}");
                }

                $requestedLocationKey = $this->locationLevel->resolveRequestedLocationKey((array)$item, $part);
                $transactionLocation = [];
                if ($requestedLocationKey !== null) {
                    $transactionLocation = $this->locationLevel->normalizeLocationPayload((array)$item, $part);
                }
                $locationAllocation = $this->locationLevel->removeStock($partId, $requestedQty, (array)$item, $part);

                $issueBreakdown = $this->part->getFifoIssueBreakdown($partId, $requestedQty);
                $allocatedQty = 0;
                $allocatedValue = 0.0;

                foreach ($issueBreakdown as $layer) {
                    $layerQty = (int)($layer['quantity'] ?? 0);
                    if ($layerQty <= 0) {
                        continue;
                    }

                    $layerUnitPrice = round(max(0, (float)($layer['unit_price'] ?? 0)), 2);
                    $allocatedQty += $layerQty;
                    $allocatedValue += $layerQty * $layerUnitPrice;
                }

                if ($allocatedQty < $requestedQty) {
                    $missingQty = $requestedQty - $allocatedQty;
                    $fallbackUnitPrice = $this->part->getFifoIssueUnitPrice($partId, $requestedQty);
                    $allocatedQty += $missingQty;
                    $allocatedValue += $missingQty * $fallbackUnitPrice;
                    $issueBreakdown[] = [
                        'quantity' => $missingQty,
                        'unit_price' => round($fallbackUnitPrice, 2),
                    ];

                }

                $fallbackLocation = !empty($transactionLocation)
                    ? $transactionLocation
                    : $this->locationLevel->normalizeLocationPayload([], $part);

                $checkoutEntries = $this->buildCheckoutTransactionEntries(
                    $issueBreakdown,
                    (array)($locationAllocation['breakdown'] ?? []),
                    $requestedQty,
                    $fallbackLocation
                );

                if (empty($checkoutEntries)) {
                    throw new \RuntimeException("Unable to build checkout transactions for part {$partId}");
                }

                foreach ($checkoutEntries as $entry) {
                    $this->transaction->recordCheckout(
                        $partId,
                        (int)$entry['quantity'],
                        $refType,
                        $refId,
                        $notes,
                        $createdBy,
                        (float)$entry['unit_price'],
                        $checkoutAt,
                        (array)($entry['location'] ?? [])
                    );
                }

                $newStock = $this->part->adjustStock($partId, -$requestedQty);
                $this->part->syncUnitPriceFromFifo($partId);

                $results[] = [
                    'part_id' => $partId,
                    'quantity_removed' => $requestedQty,
                    'new_stock' => $newStock,
                    'unit_price' => $allocatedQty > 0 ? round($allocatedValue / $allocatedQty, 2) : 0.0,
                    'issue_breakdown' => $issueBreakdown,
                    'location_breakdown' => $locationAllocation['breakdown'] ?? [],
                ];
            }

            $db->commit();

            Response::success([
                'reference_type' => $refType,
                'reference_id' => $refId,
                'items' => $results,
            ], 'Checkout processed successfully');
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('Checkout processing failed', 500, $e->getMessage());
        }
    }
    
    /**
     * Process return (parts coming back from WO/Unit/Tech)
     */
    public function return(): void
    {
        $data = $this->validate([
            'part_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'reference_type' => 'required|string',
            'reference_id' => 'required|integer',
        ]);
        
        $part = $this->part->find($data['part_id']);
        if (!$part) {
            Response::notFound('Part not found');
        }
        
        $notes = $this->request->get('notes');
        $locationPayload = $this->buildLocationPayload();
        $normalizedLocation = $this->locationLevel->normalizeLocationPayload($locationPayload, $part);
        
        // Get logged-in user info (use display name)
        $createdBy = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? null;
        $returnUnitPrice = $this->part->getReferenceOutstandingIssueUnitPrice(
            (int)$data['part_id'],
            (string)$data['reference_type'],
            (int)$data['reference_id'],
            (int)$data['quantity']
        );

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $newStock = $this->part->adjustStock($data['part_id'], $data['quantity']);
            $this->locationLevel->addStock((int)$data['part_id'], (int)$data['quantity'], $normalizedLocation, $part);

            $this->transaction->recordReturn(
                $data['part_id'],
                $data['quantity'],
                $data['reference_type'],
                $data['reference_id'],
                $notes,
                $createdBy,
                $returnUnitPrice,
                null,
                $normalizedLocation
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Response::error('Return processing failed', 500, $e->getMessage());
        }

        $this->part->syncUnitPriceFromFifo((int)$data['part_id']);

        Response::success([
            'part_id' => $data['part_id'],
            'quantity_returned' => $data['quantity'],
            'new_stock' => $newStock,
            'location' => $normalizedLocation['location_display'] ?? null,
        ], 'Return processed successfully');
    }
    
    /**
     * Get all stock levels
     */
    public function stockLevels(): void
    {
        $sql = "SELECT p.id, p.fowler_part_number, p.name, 
                       COALESCE(il.quantity, 0) as stock,
                       p.low_stock_threshold
                FROM parts p
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                ORDER BY p.fowler_part_number";
        $levels = Database::query($sql)->fetchAll();
        Response::success($levels);
    }
    
    /**
     * Get transaction history
     */
    public function transactions(): void
    {
        $partId = $this->request->query('part_id');
        $partNumber = $this->request->query('part_number');
        $limitRaw = $this->request->query('limit', '__missing__');
        $limitProvided = $limitRaw !== '__missing__';
        $limit = null;
        if ($limitProvided) {
            if (is_string($limitRaw) && strtolower($limitRaw) === 'all') {
                $limit = 0;
            } elseif (is_numeric($limitRaw)) {
                $limit = (int)$limitRaw;
            }
        }
        $effectiveLimit = $limitProvided ? $limit : 100;
        
        if ($partId) {
            $transactions = $this->transaction->getByPart((int)$partId);
        } elseif ($partNumber) {
            $transactions = $this->transaction->search(['part_number' => $partNumber], $effectiveLimit);
        } else {
            $transactions = $this->transaction->getRecent($effectiveLimit);
        }
        
        Response::success($transactions);
    }
    
    /**
     * Validate the existence of the reference entity (work order, unit, technician)
     */
    private function validateReference(string $refType, int $refId): void
    {
        $model = null;
        $modelName = '';

        switch ($refType) {
            case 'work_order':
                $model = $this->workOrder;
                $modelName = 'Work Order';
                break;
            case 'unit':
                $model = $this->unit;
                $modelName = 'Unit';
                break;
            case 'technician':
                $model = $this->technician;
                $modelName = 'Technician';
                break;
            default:
                Response::error("Invalid reference_type: {$refType}", 422);
        }

        if (!$model->find($refId)) {
            Response::notFound("{$modelName} with ID {$refId} not found");
        }
    }

    /**
     * Validate return quantity against what was taken
     */
    private function validateReturn(int $partId, int $returnQty, string $refType, int $refId): void
    {
        // Get all outgoing transactions for this part and reference
        $sql = "SELECT SUM(ABS(quantity)) as total_taken
                FROM inventory_transactions
                WHERE part_id = ? 
                AND transaction_type = 'outgoing'
                AND reference_type = ?
                AND reference_id = ?";
        
        $result = Database::query($sql, [$partId, $refType, $refId])->fetch();
        $totalTaken = $result['total_taken'] ?? 0;
        
        // Get all returns for this part and reference
        $sql = "SELECT SUM(quantity) as total_returned
                FROM inventory_transactions
                WHERE part_id = ? 
                AND transaction_type = 'return'
                AND reference_type = ?
                AND reference_id = ?";
        
        $result = Database::query($sql, [$partId, $refType, $refId])->fetch();
        $totalReturned = $result['total_returned'] ?? 0;
        
        // Calculate available to return
        $availableToReturn = $totalTaken - $totalReturned;
        
        // Validate
        if ($totalTaken == 0) {
            $part = $this->part->find($partId);
            $partName = $part ? $part['fowler_part_number'] : "Part ID {$partId}";
            Response::error(
                "Cannot return {$partName}: No outgoing record found for this part and reference",
                422
            );
        }
        
        if ($returnQty > $availableToReturn) {
            $part = $this->part->find($partId);
            $partName = $part ? $part['fowler_part_number'] : "Part ID {$partId}";
            Response::error(
                "Cannot return {$returnQty} of {$partName}: Only {$availableToReturn} available to return (taken: {$totalTaken}, already returned: {$totalReturned})",
                422
            );
        }
    }
    
    /**
     * Get returnable items for a specific reference (work_order, unit, technician)
     */
    public function returnableItems(): void
    {
        $refType = $this->request->query('reference_type');
        $refId = $this->request->query('reference_id');
        
        if (!$refType || !$refId) {
            Response::error('reference_type and reference_id are required', 422);
        }
        
        // Get all parts that were sent out to this reference
        $sql = "SELECT 
                    p.id as part_id,
                    p.fowler_part_number,
                    p.name as part_name,
                    SUM(ABS(outgoing.quantity)) as total_taken,
                    COALESCE(SUM(returns.quantity), 0) as total_returned,
                    (SUM(ABS(outgoing.quantity)) - COALESCE(SUM(returns.quantity), 0)) as available_to_return
                FROM inventory_transactions outgoing
                JOIN parts p ON outgoing.part_id = p.id
                LEFT JOIN inventory_transactions returns ON 
                    returns.part_id = outgoing.part_id 
                    AND returns.transaction_type = 'return'
                    AND returns.reference_type = outgoing.reference_type
                    AND returns.reference_id = outgoing.reference_id
                WHERE outgoing.transaction_type = 'outgoing'
                AND outgoing.reference_type = ?
                AND outgoing.reference_id = ?
                GROUP BY p.id, p.fowler_part_number, p.name
                HAVING available_to_return > 0
                ORDER BY p.fowler_part_number";
        
        $items = Database::query($sql, [$refType, $refId])->fetchAll();
        
        Response::success($items);
    }

    private function buildCheckoutTransactionEntries(
        array $issueBreakdown,
        array $locationBreakdown,
        int $requestedQty,
        array $fallbackLocation
    ): array {
        $targetQty = max(0, $requestedQty);
        if ($targetQty === 0) {
            return [];
        }

        $layers = [];
        $layerTotal = 0;
        foreach ($issueBreakdown as $layer) {
            $layerQty = (int)($layer['quantity'] ?? 0);
            if ($layerQty <= 0 || $layerTotal >= $targetQty) {
                continue;
            }

            if ($layerTotal + $layerQty > $targetQty) {
                $layerQty = $targetQty - $layerTotal;
            }

            if ($layerQty <= 0) {
                continue;
            }

            $layers[] = [
                'quantity' => $layerQty,
                'unit_price' => round(max(0, (float)($layer['unit_price'] ?? 0)), 2),
            ];
            $layerTotal += $layerQty;
        }

        if ($layerTotal <= 0) {
            return [];
        }

        $locations = [];
        $locationTotal = 0;
        foreach ($locationBreakdown as $locationRow) {
            $locationQty = (int)($locationRow['quantity'] ?? 0);
            if ($locationQty <= 0 || $locationTotal >= $targetQty) {
                continue;
            }

            if ($locationTotal + $locationQty > $targetQty) {
                $locationQty = $targetQty - $locationTotal;
            }

            if ($locationQty <= 0) {
                continue;
            }

            $locations[] = [
                'quantity' => $locationQty,
                'location' => $this->sanitizeLocationPayload((array)$locationRow),
            ];
            $locationTotal += $locationQty;
        }

        $fallback = $this->sanitizeLocationPayload($fallbackLocation);
        if ($locationTotal <= 0) {
            $locations[] = [
                'quantity' => $targetQty,
                'location' => $fallback,
            ];
            $locationTotal = $targetQty;
        } elseif ($locationTotal < $targetQty) {
            $locations[] = [
                'quantity' => $targetQty - $locationTotal,
                'location' => $fallback,
            ];
            $locationTotal = $targetQty;
        }

        $entries = [];
        $layerIndex = 0;
        $locationIndex = 0;
        $remainingLayerQty = (int)($layers[0]['quantity'] ?? 0);
        $remainingLocationQty = (int)($locations[0]['quantity'] ?? 0);

        while ($layerIndex < count($layers) && $locationIndex < count($locations)) {
            $qty = min($remainingLayerQty, $remainingLocationQty);
            if ($qty > 0) {
                $entries[] = [
                    'quantity' => $qty,
                    'unit_price' => (float)$layers[$layerIndex]['unit_price'],
                    'location' => (array)$locations[$locationIndex]['location'],
                ];
            }

            $remainingLayerQty -= $qty;
            $remainingLocationQty -= $qty;

            if ($remainingLayerQty <= 0) {
                $layerIndex++;
                if ($layerIndex < count($layers)) {
                    $remainingLayerQty = (int)$layers[$layerIndex]['quantity'];
                }
            }

            if ($remainingLocationQty <= 0) {
                $locationIndex++;
                if ($locationIndex < count($locations)) {
                    $remainingLocationQty = (int)$locations[$locationIndex]['quantity'];
                }
            }
        }

        $recordedQty = 0;
        foreach ($entries as $entry) {
            $recordedQty += (int)($entry['quantity'] ?? 0);
        }

        if ($recordedQty < $targetQty && !empty($layers)) {
            $lastLayer = $layers[count($layers) - 1];
            $entries[] = [
                'quantity' => $targetQty - $recordedQty,
                'unit_price' => (float)($lastLayer['unit_price'] ?? 0),
                'location' => $fallback,
            ];
        }

        return $entries;
    }

    private function sanitizeLocationPayload(array $location): array
    {
        $keys = ['location_key', 'location_aisle', 'location_shelf', 'location_bay', 'location_alt'];
        $clean = [];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $location)) {
                continue;
            }

            $value = trim((string)$location[$key]);
            if ($value !== '') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    private function buildLocationPayload(): array
    {
        return [
            'location_key' => $this->request->get('location_key'),
            'location_raw' => $this->request->get('location_raw'),
            'location_aisle' => $this->request->get('location_aisle'),
            'location_shelf' => $this->request->get('location_shelf'),
            'location_bay' => $this->request->get('location_bay'),
            'location_alt' => $this->request->get('location_alt'),
        ];
    }

    private function normalizeUnitOfMeasure($value): string
    {
        $unit = strtolower(trim(preg_replace('/\s+/', ' ', (string)($value ?? ''))));
        return $unit !== '' ? $unit : 'each';
    }
}
