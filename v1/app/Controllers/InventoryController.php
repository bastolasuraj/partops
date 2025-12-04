<?php
/**
 * Inventory Controller
 * 
 * Handle inventory movements (receive, checkout, return, adjust)
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;
use PartOps\Models\InventoryLevel;
use PartOps\Models\InventoryMove;
use PartOps\Models\Part;
use PartOps\Models\Location;
use PartOps\Models\Supplier;
use PartOps\Models\Technician;
use PartOps\Models\PartSupplier;

class InventoryController extends Controller
{
    private InventoryLevel $inventoryLevel;
    private InventoryMove $inventoryMove;
    private Part $partModel;
    private Location $locationModel;
    private Supplier $supplierModel;
    private Technician $technicianModel;
    private PartSupplier $partSupplierModel;

    public function __construct()
    {
        $this->inventoryLevel = new InventoryLevel();
        $this->inventoryMove = new InventoryMove();
        $this->partModel = new Part();
        $this->locationModel = new Location();
        $this->supplierModel = new Supplier();
        $this->technicianModel = new Technician();
        $this->partSupplierModel = new PartSupplier();
    }

    public function index(): void
    {
        $this->requireAuth();
        $moves = $this->inventoryMove->getRecent();
        $this->view('inventory.index', ['title' => 'Inventory', 'moves' => $moves]);
    }

    public function receiveForm(): void
    {
        $this->requireAuth();
        $parts = $this->partModel->all(['is_active' => 1]);
        $suppliers = $this->supplierModel->all(['is_active' => 1]);
        $technicians = $this->technicianModel->getActive();
        $partLocations = $this->buildPartLocationMap($parts);
        
        $this->view('inventory.receive', [
            'title' => 'Receive Parts',
            'parts' => $parts,
            'suppliers' => $suppliers,
            'technicians' => $technicians,
            'partLocations' => $partLocations,
            'csrf_token' => $this->generateCsrf(),
            'idempotency_key' => bin2hex(random_bytes(16))
        ]);
    }

    public function processReceive(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $receiptType = $this->post('receipt_type', 'new_stock');

        try {
            if ($receiptType === 'tech_return') {
                // Process batch returns from work order
                $returnsJson = $this->post('returns');
                $returns = json_decode($returnsJson, true);
                
                if (!$returns || !is_array($returns) || empty($returns)) {
                    $this->json(['error' => 'No returns specified'], 400);
                }
                
                $idempotencyKey = $this->post('idempotency_key');
                $notes = $this->post('notes', '');
                
                foreach ($returns as $index => $return) {
                    $partId = (int)$return['part_id'];
                    $locationId = (int)$return['location_id'];
                    $qty = (int)$return['qty'];
                    $workOrderRef = trim($return['work_order_ref']);
                    $unitNumber = trim($return['unit_number'] ?? '');
                    
                    if (!$partId || !$locationId || $qty <= 0) {
                        continue; // Skip invalid entries
                    }
                    
                    // Create unique idempotency key for each item
                    $itemIdempotencyKey = $idempotencyKey . '-' . $index;
                    
                    $this->inventoryMove->createMove([
                        'part_id' => $partId,
                        'location_id' => $locationId,
                        'qty' => $qty,
                        'direction' => 'in',
                        'reason' => 'tech_return',
                        'work_order_ref' => $workOrderRef,
                        'unit_number' => $unitNumber ?: null,
                        'idempotency_key' => $itemIdempotencyKey,
                        'notes' => $notes
                    ]);
                    
                    $this->inventoryLevel->adjustStock($partId, $locationId, $qty);
                }
                
                $this->json(['success' => true]);
            } else {
                // New stock from supplier (original logic)
                $partId = (int)$this->post('part_id');
                $locationAisle = trim((string)$this->post('location_aisle', ''));
                $locationShelf = trim((string)$this->post('location_shelf', ''));
                $locationBay = trim((string)$this->post('location_bay', ''));
                $qty = (int)$this->post('qty');

                if (!$partId || $qty <= 0 || $locationAisle === '' || $locationShelf === '' || $locationBay === '') {
                    $this->json(['error' => 'Invalid input data'], 400);
                }

                $locationId = $this->locationModel->findOrCreate($locationAisle, $locationShelf, $locationBay);
                $supplierId = (int)$this->post('supplier_id');
                $supplierSku = trim($this->post('supplier_sku', ''));
                $priceAtTx = (float)$this->post('price', 0);
                $coreCharge = (float)$this->post('core_charge', 0);
                $expectedRebate = (float)$this->post('expected_rebate', 0);

                $this->inventoryMove->createMove([
                    'part_id' => $partId,
                    'location_id' => $locationId,
                    'qty' => $qty,
                    'direction' => 'in',
                    'reason' => 'receive',
                    'supplier_id' => $supplierId ?: null,
                    'supplier_sku' => $supplierSku,
                    'price_at_tx' => $priceAtTx,
                    'core_charge_at_tx' => $coreCharge,
                    'core_rebate_expected' => $expectedRebate,
                    'idempotency_key' => $this->post('idempotency_key'),
                    'notes' => $this->post('notes')
                ]);

                // Keep supplier catalog mapping in sync with incoming receipts
                if ($supplierId && $supplierSku !== '') {
                    $this->partSupplierModel->addOrUpdate($partId, $supplierId, [
                        'supplier_sku' => $supplierSku,
                        'price' => $priceAtTx,
                        'core_charge' => $coreCharge,
                        'expected_rebate' => $expectedRebate
                    ]);
                }

                $this->inventoryLevel->adjustStock($partId, $locationId, $qty);
                $this->redirect('/inventory');
            }
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchWorkOrders(): void
    {
        $this->requireAuth();
        
        $query = trim($this->get('q', ''));
        
        if (strlen($query) < 2) {
            $this->json(['workorders' => []], 200);
            return;
        }
        
        try {
            $results = $this->inventoryMove->searchWorkOrders($query);
            
            $workorders = [];
            foreach ($results as $row) {
                $workorders[] = [
                    'work_order_ref' => $row['work_order_ref'],
                    'unit_number' => $row['unit_number'] ?? '',
                    'parts_count' => 1
                ];
            }
            
            $this->json(['workorders' => $workorders]);
        } catch (\Exception $e) {
            error_log('searchWorkOrders error: ' . $e->getMessage());
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getWorkOrderParts(): void
    {
        $this->requireAuth();
        
        $workOrderRef = trim($this->get('wo', ''));
        
        if (!$workOrderRef) {
            $this->json(['error' => 'Work order number required'], 400);
            return;
        }
        
        try {
            $results = $this->inventoryMove->getWorkOrderParts($workOrderRef);
            
            $parts = [];
            foreach ($results as $row) {
                $parts[] = [
                    'part_id' => $row['part_id'],
                    'location_id' => $row['location_id'],
                    'anchor_slug' => $row['anchor_slug'],
                    'aisle' => $row['aisle'],
                    'shelf' => $row['shelf'],
                    'bay' => $row['bay'],
                    'unit_number' => $row['unit_number'] ?? '',
                    'qty_checked_out' => (int)$row['total_out'] - (int)$row['total_returned']
                ];
            }
            
            $this->json(['parts' => $parts]);
        } catch (\Exception $e) {
            error_log('getWorkOrderParts error: ' . $e->getMessage());
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkoutForm(): void
    {
        $this->requireAuth();
        $parts = $this->partModel->all(['is_active' => 1]);
        $technicians = $this->technicianModel->getActive();
        $partLocations = $this->buildPartLocationMap($parts);
        
        $this->view('inventory.checkout', [
            'title' => 'Checkout Parts',
            'parts' => $parts,
            'technicians' => $technicians,
            'partLocations' => $partLocations,
            'csrf_token' => $this->generateCsrf(),
            'idempotency_key' => bin2hex(random_bytes(16))
        ]);
    }

    public function processCheckout(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $partId = (int)$this->post('part_id');
        $locationAisle = trim((string)$this->post('location_aisle', ''));
        $locationShelf = trim((string)$this->post('location_shelf', ''));
        $locationBay = trim((string)$this->post('location_bay', ''));
        $qty = (int)$this->post('qty');
        $workOrderRef = trim((string)$this->post('work_order_ref', ''));
        $unitNumber = trim((string)$this->post('unit_number', ''));
        $technicianId = (int)$this->post('technician_id');

        if (!$partId || $qty <= 0 || !$technicianId || $workOrderRef === '' || $locationAisle === '' || $locationShelf === '' || $locationBay === '') {
            $this->json(['error' => 'Invalid input data'], 400);
        }

        try {
            $locationId = $this->locationModel->findOrCreate($locationAisle, $locationShelf, $locationBay);
            // Check stock first
            $level = $this->inventoryLevel->getByPartAndLocation($partId, $locationId);
            if (!$level || $level['on_hand'] < $qty) {
                throw new \Exception("Insufficient stock. Available: " . ($level['on_hand'] ?? 0));
            }

            $this->inventoryMove->createMove([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $qty,
                'direction' => 'out',
                'reason' => 'checkout',
                'work_order_ref' => $workOrderRef,
                'unit_number' => $unitNumber ?: null,
                'technician_id' => $technicianId,
                'idempotency_key' => $this->post('idempotency_key'),
                'notes' => $this->post('notes')
            ]);

            $this->inventoryLevel->adjustStock($partId, $locationId, -$qty);
            
            $this->redirect('/inventory');
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function returnForm(): void
    {
        $this->requireAuth();
        $parts = $this->partModel->all(['is_active' => 1]);
        $suppliers = $this->supplierModel->all(['is_active' => 1]);
        $partLocations = $this->buildPartLocationMap($parts);
        
        $this->view('inventory.return', [
            'title' => 'Returns',
            'parts' => $parts,
            'suppliers' => $suppliers,
            'partLocations' => $partLocations,
            'csrf_token' => $this->generateCsrf(),
            'idempotency_key' => bin2hex(random_bytes(16))
        ]);
    }

    public function processReturn(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $partId = (int)$this->post('part_id');
        $locationAisle = trim((string)$this->post('location_aisle', ''));
        $locationShelf = trim((string)$this->post('location_shelf', ''));
        $locationBay = trim((string)$this->post('location_bay', ''));
        $qty = (int)$this->post('qty');
        $reason = $this->post('reason', 'return'); // return or core_return

        if (!$partId || $qty <= 0 || $locationAisle === '' || $locationShelf === '' || $locationBay === '') {
            $this->json(['error' => 'Invalid input data'], 400);
        }

        try {
            $locationId = $this->locationModel->findOrCreate($locationAisle, $locationShelf, $locationBay);
            // Check stock (since we are returning TO supplier, we remove from our stock)
            $level = $this->inventoryLevel->getByPartAndLocation($partId, $locationId);
            if (!$level || $level['on_hand'] < $qty) {
                throw new \Exception("Insufficient stock to return. Available: " . ($level['on_hand'] ?? 0));
            }

            $this->inventoryMove->createMove([
                'part_id' => $partId,
                'location_id' => $locationId,
                'qty' => $qty,
                'direction' => 'out',
                'reason' => $reason,
                'supplier_id' => (int)$this->post('supplier_id') ?: null,
                'core_charge_at_tx' => (float)$this->post('core_charge', 0),
                'core_rebate_expected' => (float)$this->post('expected_rebate', 0),
                'core_rebate_received' => (float)$this->post('rebate_received', 0),
                'core_due_state' => $this->post('core_due_state', 'none'),
                'idempotency_key' => $this->post('idempotency_key'),
                'notes' => $this->post('notes')
            ]);

            $this->inventoryLevel->adjustStock($partId, $locationId, -$qty);
            
            $this->redirect('/inventory');
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function adjustForm(): void
    {
        $this->requireAuth();
        $parts = $this->partModel->all(['is_active' => 1]);
        $suppliers = $this->supplierModel->all(['is_active' => 1]);
        $locations = $this->locationModel->all(['is_active' => 1]);
        
        $this->view('inventory.adjust', [
            'title' => 'Adjustments',
            'parts' => $parts,
            'suppliers' => $suppliers,
            'locations' => $locations,
            'csrf_token' => $this->generateCsrf()
        ]);
    }

    public function processAdjust(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $type = $this->post('adjustment_type');
        $partId = (int)$this->post('part_id');

        if (!$partId) {
            $this->json(['error' => 'Part is required'], 400);
        }

        try {
            switch ($type) {
                case 'quantity':
                    $locationId = (int)$this->post('location_id');
                    $qtyDelta = (int)$this->post('qty_delta'); // Can be negative
                    if (!$locationId || $qtyDelta == 0) throw new \Exception("Invalid location or quantity");
                    
                    $this->inventoryMove->createMove([
                        'part_id' => $partId,
                        'location_id' => $locationId,
                        'qty' => abs($qtyDelta),
                        'direction' => $qtyDelta > 0 ? 'in' : 'out',
                        'reason' => 'adjust',
                        'notes' => $this->post('notes')
                    ]);
                    $this->inventoryLevel->adjustStock($partId, $locationId, $qtyDelta);
                    break;

                case 'part_number':
                    $this->partModel->addNumber($partId, [
                        'value' => trim($this->post('pn_value', '')),
                        'type' => $this->post('pn_type', 'active'),
                        'manufacturer' => trim($this->post('pn_mfr', '')),
                        'is_primary' => 0
                    ]);
                    break;

                case 'supplier':
                    $supplierId = (int)$this->post('supplier_id');
                    if (!$supplierId) throw new \Exception("Supplier required");
                    
                    $this->partSupplierModel->addOrUpdate($partId, $supplierId, [
                        'supplier_sku' => trim($this->post('supplier_sku', '')),
                        'price' => (float)$this->post('price', 0)
                    ]);
                    break;

                default:
                    throw new \Exception("Invalid adjustment type");
            }
            
            $this->redirect('/inventory');
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    private function buildPartLocationMap(array $parts): array
    {
        $map = [];
        foreach ($parts as $part) {
            $levels = $this->inventoryLevel->getByPart((int)$part['id']);
            if (!empty($levels)) {
                $first = $levels[0];
                $map[$part['id']] = [
                    'aisle' => $first['aisle'],
                    'shelf' => $first['shelf'],
                    'bay' => $first['bay'],
                    'bin' => $first['bin'] ?? ''
                ];
            }
        }
        return $map;
    }
}
