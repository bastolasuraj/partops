<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Response;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\InventoryTransaction;
use App\Models\InventoryLocationLevel;

class PartController extends BaseController
{
    private Part $part;
    private Supplier $supplier;
    private InventoryTransaction $transaction;
    private InventoryLocationLevel $locationLevel;
    private static bool $partsSchemaChecked = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->ensurePartsSchema();
        $this->part = new Part();
        $this->supplier = new Supplier();
        $this->transaction = new InventoryTransaction();
        $this->locationLevel = new InventoryLocationLevel();
    }
    
    public function index(): void
    {
        $search = $this->request->query('search');
        $parts = $this->part->getAllWithDetails($search);
        Response::success($parts);
    }
    
    public function show(int $id): void
    {
        $part = $this->part->findWithDetails($id);
        
        if (!$part) {
            Response::notFound('Part not found');
        }
        
        Response::success($part);
    }
    
    public function store(): void
    {
        $data = $this->validate([
            'fowler_part_number' => 'string',
            'name' => 'required|string|min:2',
            'supplier_id' => 'integer',
            'supplier_part_number' => 'required|string|min:2',
            'unit_of_measure' => 'string|max:30',
            'location_alt' => 'string',
            'low_stock_threshold' => 'integer|min:0',
        ]);
        
        // Check for duplicate supplier_part_number (this is the unique identifier)
        $existing = $this->part->findBySupplierPartNumber($data['supplier_part_number']);
        if ($existing) {
            Response::error('Supplier Part Number already exists', 422);
        }
        
        // Set defaults and sanitize numeric fields
        $fowlerPartNumber = $this->normalizeFowlerPartNumber($data['fowler_part_number'] ?? '');
        if ($fowlerPartNumber === '') {
            $fowlerPartNumber = $this->formatFowlerPartNumber($this->getNextFowlerSequence());
        }
        $data['fowler_part_number'] = $fowlerPartNumber;
        $data['unit_of_measure'] = $this->normalizeUnitOfMeasure($data['unit_of_measure'] ?? null);
        $data['low_stock_threshold'] = !empty($data['low_stock_threshold']) ? intval($data['low_stock_threshold']) : 5;
        
        $part = $this->part->create($data);
        
        // Initialize inventory level
        $this->part->updateStock($part['id'], 0);
        
        // Create mapping entry in fowler_supplier_mapping table
        $this->createFowlerSupplierMapping(
            $fowlerPartNumber,
            $part['id'],
            $data['supplier_id'] ?? null,
            $data['supplier_part_number']
        );
        
        $part = $this->part->findWithDetails($part['id']);
        Response::created($part, 'Part created successfully');
    }
    
    public function update(int $id): void
    {
        $existing = $this->part->find($id);
        if (!$existing) {
            Response::notFound('Part not found');
        }
        
        $data = $this->request->all();
        unset($data['stock']); // Stock is derived from inventory_levels, never writable on parts
        
        if (array_key_exists('fowler_part_number', $data)) {
            $normalizedFowler = $this->normalizeFowlerPartNumber($data['fowler_part_number']);
            if ($normalizedFowler === '') {
                unset($data['fowler_part_number']);
            } else {
                $data['fowler_part_number'] = $normalizedFowler;
            }
        }

        if (array_key_exists('unit_of_measure', $data)) {
            $data['unit_of_measure'] = $this->normalizeUnitOfMeasure($data['unit_of_measure']);
        }
        
        // Check for duplicate supplier_part_number if changed (this is the unique identifier)
        if (isset($data['supplier_part_number']) && $data['supplier_part_number'] !== $existing['supplier_part_number']) {
            $duplicate = $this->part->findBySupplierPartNumber($data['supplier_part_number']);
            if ($duplicate) {
                Response::error('Supplier Part Number already exists', 422);
            }
        }
        
        $this->part->update($id, $data);
        $part = $this->part->findWithDetails($id);
        Response::success($part, 'Part updated successfully');
    }
    
    public function destroy(int $id): void
    {
        $existing = $this->part->find($id);
        if (!$existing) {
            Response::notFound('Part not found');
        }
        
        try {
            $this->part->delete($id);
            Response::success(null, 'Part deleted successfully');
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400); // Bad request for trying to delete part with stock
        }
    }
    
    public function checkDuplicate(): void
    {
        $supplierPartNumber = $this->request->query('supplier_part_number');
        
        if (!$supplierPartNumber) {
            Response::error('supplier_part_number is required', 400);
        }
        
        $existing = $this->part->findBySupplierPartNumber($supplierPartNumber);
        
        Response::success([
            'exists' => $existing !== null,
            'part' => $existing,
        ]);
    }

    public function checkFowlerPartNumber(): void
    {
        $fowlerPartNumber = $this->normalizeFowlerPartNumber($this->request->query('fowler_part_number'));

        if ($fowlerPartNumber === '') {
            Response::error('fowler_part_number is required', 400);
        }

        $parts = $this->part->findByFowlerPartNumber($fowlerPartNumber);
        if (empty($parts)) {
            Response::success([
                'exists' => false,
                'count' => 0,
                'part' => null,
            ]);
            return;
        }

        $primaryPartId = \App\Core\Database::query(
            "SELECT part_id
             FROM fowler_supplier_mapping
             WHERE fowler_part_number = ?
             ORDER BY is_primary DESC, created_at ASC
             LIMIT 1",
            [$fowlerPartNumber]
        )->fetch();

        $part = null;
        if ($primaryPartId && isset($primaryPartId['part_id'])) {
            foreach ($parts as $candidate) {
                if ((int)$candidate['id'] === (int)$primaryPartId['part_id']) {
                    $part = $candidate;
                    break;
                }
            }
        }

        if (!$part) {
            $part = $parts[0];
        }

        Response::success([
            'exists' => true,
            'count' => count($parts),
            'part' => $part,
        ]);
    }

    public function nextFowlerPartNumber(): void
    {
        $next = $this->formatFowlerPartNumber($this->getNextFowlerSequence());
        Response::success(['fowler_part_number' => $next]);
    }
    
    public function lowStock(): void
    {
        $parts = $this->part->getLowStock();
        Response::success($parts);
    }
    
    public function getStock(int $id): void
    {
        $part = $this->part->find($id);
        if (!$part) {
            Response::notFound('Part not found');
        }
        
        $stock = $this->part->getStock($id);
        $locations = $this->locationLevel->getByPart($id, $part);
        Response::success([
            'part_id' => $id,
            'stock' => $stock,
            'locations' => $locations,
        ]);
    }

    public function getLocations(int $id): void
    {
        $part = $this->part->find($id);
        if (!$part) {
            Response::notFound('Part not found');
        }

        $locations = $this->locationLevel->getByPart($id, $part);
        Response::success([
            'part_id' => $id,
            'locations' => $locations,
        ]);
    }

    public function bulkImport(): void
    {
        $items = $this->request->get('items', []);
        if (!is_array($items)) {
            Response::error('items must be an array', 400);
        }

        $stats = [
            'processed' => 0,
            'created' => 0,
            'duplicates' => 0,
            'existing_updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'suppliers_created' => 0,
        ];
        $errors = [];
        $duplicateItems = [];
        $nextFowlerNumber = $this->getNextFowlerSequence();
        $createdBy = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? null;
        $createdInBatch = [];

        foreach ($items as $index => $item) {
            $stats['processed']++;
            if (!is_array($item)) {
                $stats['skipped']++;
                continue;
            }

            $supplierPartNumber = $this->normalizeString($item['supplier_part_number'] ?? '');
            $name = $this->normalizeString($item['name'] ?? '');
            $description = $this->normalizeString($item['description'] ?? '');
            $incomingUnitOfMeasure = $this->normalizeUnitOfMeasure($item['unit_of_measure'] ?? null);

            if ($description === '' && $name !== '') {
                $description = $name;
            }
            if ($name === '' && $description !== '') {
                $name = $description;
            }

            if ($supplierPartNumber === '' || $name === '') {
                $stats['skipped']++;
                continue;
            }

            $locationAisle = $this->normalizeString($item['location_aisle'] ?? '');
            $locationShelf = $this->normalizeString($item['location_shelf'] ?? '');
            $locationBay = $this->normalizeString($item['location_bay'] ?? '');
            $locationAlt = $this->normalizeString($item['location_alt'] ?? '');
            $locationRaw = $this->normalizeString($item['location_raw'] ?? '');

            if ($locationRaw !== '') {
                [$locationAisle, $locationShelf, $locationBay, $parsedAlt] = $this->parseLocationStrict($locationRaw);
                if ($parsedAlt !== '') {
                    $locationAlt = $parsedAlt;
                }
            }

            $quantity = $this->parseQuantity($item['quantity'] ?? 0);
            $unitPrice = $this->parseUnitPrice($item['unit_price'] ?? 0);

            if ($unitPrice <= 0) {
                $stats['skipped']++;
                if (count($errors) < 25) {
                    $errors[] = [
                        'row' => $index + 1,
                        'supplier_part_number' => $supplierPartNumber,
                        'message' => 'Missing cost/unit',
                    ];
                }
                continue;
            }

            $lowStockThreshold = $item['low_stock_threshold'] ?? 5;
            $lowStockThreshold = is_numeric($lowStockThreshold) ? max(0, (int)$lowStockThreshold) : 5;
            $locationPayload = [
                'location_raw' => $locationRaw,
                'location_aisle' => $locationAisle,
                'location_shelf' => $locationShelf,
                'location_bay' => $locationBay,
                'location_alt' => $locationAlt,
            ];

            $existing = $this->part->findBySupplierPartNumber($supplierPartNumber);
            if ($existing) {
                $isCreatedInThisBatch = array_key_exists($supplierPartNumber, $createdInBatch);
                $existingUnit = $this->normalizeUnitOfMeasure($existing['unit_of_measure'] ?? null);

                if ($existingUnit !== $incomingUnitOfMeasure) {
                    $stats['duplicates']++;
                    if (count($duplicateItems) < 500) {
                        $duplicateItems[] = [
                            'sheet' => $item['source_sheet'] ?? null,
                            'row' => $item['source_row'] ?? ($index + 1),
                            'supplier_part_number' => $supplierPartNumber,
                            'incoming_name' => $name,
                            'incoming_unit_of_measure' => $incomingUnitOfMeasure,
                            'existing_part_id' => $existing['id'] ?? null,
                            'existing_fowler_part_number' => $existing['fowler_part_number'] ?? null,
                            'existing_name' => $existing['name'] ?? null,
                            'existing_unit_of_measure' => $existing['unit_of_measure'] ?? 'each',
                        ];
                    }
                    continue;
                }

                if (!$isCreatedInThisBatch) {
                    $stats['duplicates']++;
                    if (count($duplicateItems) < 500) {
                        $duplicateItems[] = [
                            'sheet' => $item['source_sheet'] ?? null,
                            'row' => $item['source_row'] ?? ($index + 1),
                            'supplier_part_number' => $supplierPartNumber,
                            'incoming_name' => $name,
                            'incoming_unit_of_measure' => $incomingUnitOfMeasure,
                            'existing_part_id' => $existing['id'] ?? null,
                            'existing_fowler_part_number' => $existing['fowler_part_number'] ?? null,
                            'existing_name' => $existing['name'] ?? null,
                            'existing_unit_of_measure' => $existing['unit_of_measure'] ?? 'each',
                        ];
                    }
                    continue;
                }

                if ($quantity <= 0) {
                    $stats['skipped']++;
                    continue;
                }

                try {
                    $normalizedLocation = $this->locationLevel->normalizeLocationPayload($locationPayload, $existing);
                    $this->part->adjustStock((int)$existing['id'], $quantity);
                    $this->locationLevel->addStock((int)$existing['id'], $quantity, $normalizedLocation, $existing);
                    $this->transaction->recordIncoming(
                        (int)$existing['id'],
                        $quantity,
                        isset($existing['supplier_id']) ? (int)$existing['supplier_id'] : null,
                        (float)$unitPrice,
                        'Bulk import (additional row)',
                        $createdBy,
                        false,
                        0,
                        0,
                        0,
                        'vendor',
                        $normalizedLocation
                    );
                    $this->part->syncUnitPriceFromFifo((int)$existing['id']);
                    $stats['existing_updated']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    if (count($errors) < 25) {
                        $errors[] = [
                            'row' => $index + 1,
                            'supplier_part_number' => $supplierPartNumber,
                            'message' => $e->getMessage(),
                        ];
                    }
                }

                continue;
            }

            $supplierName = $this->normalizeString($item['supplier_name'] ?? '');
            if ($supplierName === '') {
                $stats['skipped']++;
                continue;
            }
            $supplierId = $this->findOrCreateSupplier($supplierName, $stats);
            $fowlerPartNumber = $this->formatFowlerPartNumber($nextFowlerNumber);
            $nextFowlerNumber++;

            $normalizedLocation = $this->locationLevel->normalizeLocationPayload($locationPayload);

            $data = [
                'fowler_part_number' => $fowlerPartNumber,
                'name' => $name,
                'description' => $description,
                'supplier_id' => $supplierId,
                'supplier_part_number' => $supplierPartNumber,
                'unit_of_measure' => $incomingUnitOfMeasure,
                'location_aisle' => $normalizedLocation['location_aisle'],
                'location_shelf' => $normalizedLocation['location_shelf'],
                'location_bay' => $normalizedLocation['location_bay'],
                'location_alt' => $normalizedLocation['location_alt'],
                'low_stock_threshold' => $lowStockThreshold,
                'unit_price' => $unitPrice,
            ];

            try {
                $part = $this->part->create($data);
                if ($quantity > 0) {
                    $this->part->updateStock($part['id'], $quantity);
                    $this->locationLevel->addStock((int)$part['id'], $quantity, $normalizedLocation, $part);
                    $this->transaction->recordIncoming(
                        $part['id'],
                        $quantity,
                        $supplierId,
                        (float)$unitPrice,
                        'Bulk import',
                        $createdBy,
                        false,
                        0,
                        0,
                        0,
                        'vendor',
                        $normalizedLocation
                    );
                } else {
                    $this->part->updateStock($part['id'], 0);
                }
                $this->createFowlerSupplierMapping(
                    $fowlerPartNumber,
                    $part['id'],
                    $supplierId,
                    $supplierPartNumber
                );
                $stats['created']++;
                $createdInBatch[$supplierPartNumber] = [
                    'part_id' => (int)$part['id'],
                    'unit_of_measure' => $incomingUnitOfMeasure,
                ];
            } catch (\Exception $e) {
                $stats['errors']++;
                if (count($errors) < 25) {
                    $errors[] = [
                        'row' => $index + 1,
                        'supplier_part_number' => $supplierPartNumber,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        Response::success([
            'stats' => $stats,
            'errors' => $errors,
            'duplicate_items' => $duplicateItems,
        ], 'Bulk import completed');
    }

    private function getNextFowlerSequence(): int
    {
        try {
            $row = \App\Core\Database::query(
                "SELECT MAX(CAST(SUBSTRING(fowler_part_number, 5) AS UNSIGNED)) as max_num
                 FROM parts
                 WHERE fowler_part_number REGEXP '^FC-P[0-9]{6}$'"
            )->fetch();
            $max = isset($row['max_num']) ? (int)$row['max_num'] : 0;
            return $max + 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    private function formatFowlerPartNumber(int $sequence): string
    {
        return sprintf('FC-P%06d', max(0, $sequence));
    }

    private function parseLocationStrict(string $raw): array
    {
        $clean = $this->normalizeString($raw);
        if ($clean === '') {
            return ['', '', '', ''];
        }

        if (preg_match('/^[^\\-\\.]+-[^\\-\\.]+-[^\\-\\.]+$/', $clean)) {
            $parts = array_map('trim', explode('-', $clean));
            return [$parts[0] ?? '', $parts[1] ?? '', $parts[2] ?? '', ''];
        }

        if (preg_match('/^[^\\.]+\\.[^\\.]+\\.[^\\.]+$/', $clean)) {
            $parts = array_map('trim', explode('.', $clean));
            return [$parts[0] ?? '', $parts[1] ?? '', $parts[2] ?? '', ''];
        }

        return ['', '', '', $clean];
    }
    
    /**
     * Create or update Fowler-Supplier mapping entry
     */
    private function createFowlerSupplierMapping(
        string $fowlerPartNumber, 
        int $partId, 
        ?int $supplierId, 
        string $supplierPartNumber
    ): void {
        try {
            // Check if this is the first part with this Fowler PN (make it primary)
            $existingCount = \App\Core\Database::query(
                "SELECT COUNT(*) as count FROM fowler_supplier_mapping WHERE fowler_part_number = ?",
                [$fowlerPartNumber]
            )->fetch();
            
            $isPrimary = ($existingCount['count'] == 0) ? 1 : 0;
            
            // Insert the mapping
            \App\Core\Database::query(
                "INSERT INTO fowler_supplier_mapping 
                (fowler_part_number, part_id, supplier_id, supplier_part_number, is_primary) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    fowler_part_number = VALUES(fowler_part_number),
                    part_id = VALUES(part_id),
                    supplier_id = VALUES(supplier_id),
                    is_primary = VALUES(is_primary)",
                [$fowlerPartNumber, $partId, $supplierId, $supplierPartNumber, $isPrimary]
            );
        } catch (\Exception $e) {
            // Log error but don't fail the part creation
            error_log("Failed to create Fowler-Supplier mapping: " . $e->getMessage());
        }
    }
    
    /**
     * Get all supplier parts mapped to a Fowler PN
     */
    public function getFowlerMapping(): void
    {
        $fowlerPn = $this->request->query('fowler_pn');
        
        if (!$fowlerPn) {
            Response::error('fowler_pn parameter is required', 400);
        }
        
        $mappings = \App\Core\Database::query(
            "SELECT 
                fsm.*,
                p.name as part_name,
                COALESCE(il.quantity, 0) as stock,
                s.name as supplier_name
            FROM fowler_supplier_mapping fsm
            LEFT JOIN parts p ON fsm.part_id = p.id
            LEFT JOIN inventory_levels il ON p.id = il.part_id
            LEFT JOIN suppliers s ON fsm.supplier_id = s.id
            WHERE fsm.fowler_part_number = ?
            ORDER BY fsm.is_primary DESC, fsm.created_at ASC",
            [$fowlerPn]
        )->fetchAll();
        
        Response::success($mappings);
    }

    private function findOrCreateSupplier(string $name, array &$stats): ?int
    {
        $cleanName = $this->normalizeString($name);
        if ($cleanName === '') {
            return null;
        }

        $existing = \App\Core\Database::query(
            "SELECT id FROM suppliers WHERE LOWER(name) = LOWER(?) LIMIT 1",
            [$cleanName]
        )->fetch();

        if ($existing) {
            return (int)$existing['id'];
        }

        $created = $this->supplier->create(['name' => $cleanName]);
        if ($created && isset($created['id'])) {
            $stats['suppliers_created']++;
            return (int)$created['id'];
        }

        return null;
    }

    private function normalizeString($value): string
    {
        if ($value === null) {
            return '';
        }

        $text = is_string($value) ? $value : (string)$value;
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function normalizeFowlerPartNumber($value): string
    {
        return strtoupper($this->normalizeString($value));
    }

    /**
     * Ensure required columns exist in legacy databases.
     * Keeps API operational even when migrations were not run manually.
     */
    private function ensurePartsSchema(): void
    {
        if (self::$partsSchemaChecked) {
            return;
        }

        try {
            $requiredColumns = [
                'unit_of_measure' => "ALTER TABLE parts ADD COLUMN unit_of_measure VARCHAR(30) NOT NULL DEFAULT 'each' AFTER supplier_part_number",
                'location_alt' => "ALTER TABLE parts ADD COLUMN location_alt VARCHAR(255) NULL AFTER location_bay",
                'deleted_at' => "ALTER TABLE parts ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER unit_price",
            ];

            foreach ($requiredColumns as $column => $sql) {
                if (!$this->partsColumnExists($column)) {
                    \App\Core\Database::query($sql);
                }
            }

            self::$partsSchemaChecked = true;
        } catch (\Throwable $e) {
            error_log('Part schema check failed: ' . $e->getMessage());
        }
    }

    private function partsColumnExists(string $columnName): bool
    {
        $row = \App\Core\Database::query(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'parts'
               AND column_name = ?
             LIMIT 1",
            [$columnName]
        )->fetch();

        return $row !== false;
    }

    private function normalizeUnitOfMeasure($value): string
    {
        $unit = strtolower($this->normalizeString($value));
        if ($unit === '') {
            return 'each';
        }
        if (strlen($unit) > 30) {
            return substr($unit, 0, 30);
        }
        return $unit;
    }

    private function parseQuantity($value): int
    {
        $num = $this->normalizeNumber($value);
        return (int)round($num);
    }

    private function parseUnitPrice($value): string
    {
        $num = $this->normalizeNumber($value);
        return number_format($num, 2, '.', '');
    }

    private function normalizeNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
        if ($clean === '' || $clean === '-' || $clean === '.') {
            return 0.0;
        }

        return (float)$clean;
    }

    private function parseLocation(string $raw): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $raw));
        if ($clean === '') {
            return ['', '', ''];
        }

        $prefix = '';
        $working = $clean;

        if (preg_match('/^shelf\s*([A-Za-z0-9]+)\b/i', $clean, $matches)) {
            $prefix = 'Shelf ' . $matches[1];
            $working = trim(substr($clean, strlen($matches[0])));
            $working = ltrim($working, '- ');
        }

        $parts = preg_split('/[.\/-]+/', $working);
        $parts = array_values(array_filter(array_map('trim', $parts), 'strlen'));

        if ($prefix !== '') {
            if (count($parts) < 2) {
                $tokens = preg_split('/\s+/', $working);
                $tokens = array_values(array_filter(array_map('trim', $tokens), 'strlen'));
                if (count($tokens) >= 2) {
                    $parts = $tokens;
                }
            }

            return [$prefix, $parts[0] ?? '', $parts[1] ?? ''];
        }

        if (count($parts) >= 3) {
            return [$parts[0], $parts[1], $parts[2]];
        }

        if (count($parts) === 2) {
            return [$parts[0], $parts[1], ''];
        }

        $tokens = preg_split('/\s+/', $working);
        $tokens = array_values(array_filter(array_map('trim', $tokens), 'strlen'));
        if (count($tokens) >= 3) {
            return [$tokens[0], $tokens[1], $tokens[2]];
        }

        return [$clean, '', ''];
    }
}
