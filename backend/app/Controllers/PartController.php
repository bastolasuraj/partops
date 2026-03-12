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
            Response::success(null, 'Part archived successfully');
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

    public function analyzeBulkImport(): void
    {
        $items = $this->request->get('items', []);
        if (!is_array($items)) {
            Response::error('items must be an array', 400);
        }

        Response::success($this->analyzeImportItems($items), 'Bulk import analysis completed');
    }

    public function bulkImport(): void
    {
        $items = $this->request->get('items', []);
        if (!is_array($items)) {
            Response::error('items must be an array', 400);
        }

        Response::success($this->commitImportItems($items), 'Bulk import completed');
    }

    private function analyzeImportItems(array $items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $rows[] = $this->buildInvalidImportRow($index, 'Invalid import row', 'Re-export the spreadsheet and try again.');
                continue;
            }

            $rows[] = $this->buildImportAnalysisRow($item, $index);
        }

        $this->applyImportGroupRules($rows, false);

        $readyItems = [];
        $reviewItems = [];
        $skippedItems = [];

        foreach ($rows as $row) {
            if (($row['status'] ?? 'ready') === 'skip') {
                $skippedItems[] = $row;
                continue;
            }

            if (($row['status'] ?? 'ready') === 'review') {
                $reviewItems[] = $row;
                continue;
            }

            $readyItems[] = $row;
        }

        return [
            'summary' => $this->buildImportSummary($rows),
            'ready_items' => $readyItems,
            'review_items' => $reviewItems,
            'skipped_items' => $skippedItems,
        ];
    }

    private function commitImportItems(array $items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                $rows[] = $this->buildInvalidImportRow($index, 'Invalid import row', 'Re-export the spreadsheet and try again.');
                continue;
            }

            $rows[] = $this->buildImportAnalysisRow($item, $index);
        }

        $this->applyImportGroupRules($rows, true);

        $stats = [
            'processed' => count($rows),
            'created' => 0,
            'existing_updated' => 0,
            'master_only_created' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'errors' => 0,
            'suppliers_created' => 0,
        ];
        $errors = [];
        $nextFowlerNumber = $this->getNextFowlerSequence();
        $createdBy = $_SESSION['user']['display_name'] ?? $_SESSION['user']['username'] ?? null;
        $resolvedParts = [];

        foreach ($rows as $row) {
            $item = $row['suggested_item'] ?? [];
            $supplierPartNumber = $this->normalizeString($item['supplier_part_number'] ?? '');

            if (($row['status'] ?? 'ready') === 'skip' || $supplierPartNumber === '') {
                $stats['skipped']++;
                foreach ($this->collectBlockingImportIssues($row) as $issue) {
                    $errors[] = $this->buildImportErrorItem($row, $issue);
                }
                continue;
            }

            $existingPart = null;
            if (array_key_exists($supplierPartNumber, $resolvedParts)) {
                $existingPart = $resolvedParts[$supplierPartNumber];
            } else {
                $existingLookup = $this->part->findBySupplierPartNumber($supplierPartNumber);
                if ($existingLookup) {
                    $existingPart = $this->part->findWithDetails((int)$existingLookup['id']) ?: $existingLookup;
                }
                $resolvedParts[$supplierPartNumber] = $existingPart;
            }

            $locationPayload = $this->buildLocationPayloadFromImportItem($item);
            $quantity = $this->parseQuantity($item['quantity'] ?? 0);
            $unitPrice = (float)$this->parseUnitPrice($item['unit_price'] ?? 0);

            if ($existingPart) {
                if ($quantity <= 0) {
                    $stats['unchanged']++;
                    continue;
                }

                try {
                    $normalizedLocation = $this->locationLevel->normalizeLocationPayload($locationPayload, $existingPart);
                    $this->part->adjustStock((int)$existingPart['id'], $quantity);
                    $this->locationLevel->addStock((int)$existingPart['id'], $quantity, $normalizedLocation, $existingPart);
                    $this->transaction->recordIncoming(
                        (int)$existingPart['id'],
                        $quantity,
                        isset($existingPart['supplier_id']) ? (int)$existingPart['supplier_id'] : null,
                        $unitPrice,
                        'Bulk import',
                        $createdBy,
                        false,
                        0,
                        0,
                        0,
                        'vendor',
                        $normalizedLocation
                    );
                    $this->part->syncUnitPriceFromFifo((int)$existingPart['id']);
                    $stats['existing_updated']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $errors[] = $this->buildImportRuntimeError(
                        $row,
                        $e->getMessage(),
                        'Review the row values and retry. If the part already exists, confirm the location and unit match the existing record.'
                    );
                }

                continue;
            }

            try {
                $supplierId = $this->findOrCreateSupplier($this->normalizeString($item['supplier_name'] ?? ''), $stats);
                $fowlerPartNumber = $this->formatFowlerPartNumber($nextFowlerNumber);
                $nextFowlerNumber++;
                $normalizedLocation = $this->locationLevel->normalizeLocationPayload($locationPayload);
                $data = [
                    'fowler_part_number' => $fowlerPartNumber,
                    'name' => $this->normalizeString($item['name'] ?? ''),
                    'description' => $this->normalizeString($item['description'] ?? ''),
                    'supplier_id' => $supplierId,
                    'supplier_part_number' => $supplierPartNumber,
                    'unit_of_measure' => $this->normalizeUnitOfMeasure($item['unit_of_measure'] ?? null),
                    'location_aisle' => $normalizedLocation['location_aisle'],
                    'location_shelf' => $normalizedLocation['location_shelf'],
                    'location_bay' => $normalizedLocation['location_bay'],
                    'location_alt' => $normalizedLocation['location_alt'],
                    'low_stock_threshold' => is_numeric($item['low_stock_threshold'] ?? null)
                        ? max(0, (int)$item['low_stock_threshold'])
                        : 5,
                    'unit_price' => $unitPrice,
                ];

                $part = $this->part->create($data);
                if ($quantity > 0) {
                    $this->part->updateStock((int)$part['id'], $quantity);
                    $this->locationLevel->addStock((int)$part['id'], $quantity, $normalizedLocation, $part);
                    $this->transaction->recordIncoming(
                        (int)$part['id'],
                        $quantity,
                        $supplierId,
                        $unitPrice,
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
                    $this->part->updateStock((int)$part['id'], 0);
                    $stats['master_only_created']++;
                }

                $this->createFowlerSupplierMapping(
                    $fowlerPartNumber,
                    (int)$part['id'],
                    $supplierId,
                    $supplierPartNumber
                );
                $stats['created']++;
                $resolvedParts[$supplierPartNumber] = $this->part->findWithDetails((int)$part['id']) ?: $part;
            } catch (\Exception $e) {
                $stats['errors']++;
                $errors[] = $this->buildImportRuntimeError(
                    $row,
                    $e->getMessage(),
                    'Review the row values and retry. If the supplier part number already exists, confirm the row should update the existing part instead of creating a new one.'
                );
            }
        }

        return [
            'stats' => $stats,
            'errors' => $errors,
            'no_upload_items' => $errors,
        ];
    }

    private function buildImportAnalysisRow(array $item, int $index): array
    {
        $sheet = $this->normalizeString($item['source_sheet'] ?? $item['sheet'] ?? 'Sheet1');
        $rowNumberRaw = $item['source_row'] ?? $item['row'] ?? ($index + 1);
        $rowNumber = is_numeric($rowNumberRaw) ? (int)$rowNumberRaw : ($index + 1);
        $rawSupplierPartNumber = $this->normalizeString($item['supplier_part_number'] ?? '');
        $rawManufacturer = $this->normalizeString($item['supplier_name'] ?? '');
        $rawDescription = $this->normalizeString($item['description'] ?? $item['name'] ?? '');
        $rawLocation = $this->deriveImportRawLocation($item);
        $rawAlternateLocation = $this->normalizeString($item['location_alt'] ?? '');
        $rawQuantity = $this->normalizeString($item['quantity'] ?? '');
        $rawUnitPrice = $this->normalizeString($item['unit_price'] ?? '');
        $quantity = $this->parseQuantity($item['quantity'] ?? 0);
        $unitPrice = (float)$this->parseUnitPrice($item['unit_price'] ?? 0);
        $unitOfMeasure = $this->normalizeUnitOfMeasure($item['unit_of_measure'] ?? null);

        $row = [
            'id' => sprintf('%s:%d:%d', $sheet !== '' ? $sheet : 'Sheet1', $rowNumber, $index + 1),
            'sheet' => $sheet !== '' ? $sheet : 'Sheet1',
            'row' => $rowNumber,
            'status' => 'ready',
            'action' => 'create',
            'issues' => [],
            'modifications' => [],
            'existing_part' => null,
            'original' => [
                'supplier_part_number' => $rawSupplierPartNumber,
                'description' => $rawDescription,
                'supplier_name' => $rawManufacturer,
                'location' => $rawLocation !== '' ? $rawLocation : $rawAlternateLocation,
                'unit_of_measure' => $this->normalizeString($item['unit_of_measure'] ?? ''),
                'quantity' => $rawQuantity,
                'unit_price' => $rawUnitPrice,
            ],
            'suggested_item' => [
                'supplier_part_number' => $rawSupplierPartNumber,
                'name' => $rawDescription,
                'description' => $rawDescription,
                'supplier_name' => $rawManufacturer,
                'unit_of_measure' => $unitOfMeasure,
                'location_raw' => '',
                'location_aisle' => '',
                'location_shelf' => '',
                'location_bay' => '',
                'location_alt' => '',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'low_stock_threshold' => is_numeric($item['low_stock_threshold'] ?? null)
                    ? max(0, (int)$item['low_stock_threshold'])
                    : 5,
                'source_sheet' => $sheet !== '' ? $sheet : 'Sheet1',
                'source_row' => $rowNumber,
            ],
        ];

        if ($rawSupplierPartNumber === '') {
            $this->recordImportIssue(
                $row,
                'missing_part_number',
                'supplier_part_number',
                'error',
                'Supplier PN is required.',
                'Add a Supplier/Vendor PN.'
            );
        }

        if ($rawManufacturer === '') {
            $this->recordImportIssue(
                $row,
                'missing_manufacturer',
                'supplier_name',
                'error',
                'Manufacturer is required.',
                'Add manufacturer.'
            );
        }

        if ($rawDescription === '' && $rawManufacturer !== '') {
            $fallbackDescription = sprintf('manufacturer: %s', $rawManufacturer);
            $row['suggested_item']['name'] = $fallbackDescription;
            $row['suggested_item']['description'] = $fallbackDescription;
            $this->recordImportModification(
                $row,
                'description',
                '',
                $fallbackDescription,
                'Filled missing description from manufacturer.'
            );
            $this->recordImportIssue(
                $row,
                'description_filled_from_manufacturer',
                'description',
                'warning',
                'Description was missing, so the importer suggested "manufacturer: <manufacturer>".',
                'Accept the suggestion or edit the description before committing the import.'
            );
        }

        $location = $this->normalizeImportLocation($rawLocation, $rawAlternateLocation);
        if ($location['missing']) {
            $this->recordImportIssue(
                $row,
                'missing_location',
                'location',
                'error',
                'Location is required.',
                'Provide location value.'
            );
        } else {
            $row['suggested_item']['location_raw'] = $location['location_raw'];
            $row['suggested_item']['location_aisle'] = $location['location_aisle'];
            $row['suggested_item']['location_shelf'] = $location['location_shelf'];
            $row['suggested_item']['location_bay'] = $location['location_bay'];
            $row['suggested_item']['location_alt'] = $location['location_alt'];

            foreach ($location['modifications'] as $modification) {
                $this->recordImportModification(
                    $row,
                    'location',
                    $modification['from'],
                    $modification['to'],
                    $modification['reason']
                );
            }

            if (!empty($location['modifications'])) {
                $this->recordImportIssue(
                    $row,
                    'location_cleaned',
                    'location',
                    'warning',
                    'Location was cleaned to match aisle-shelf-bay format.',
                    'Review the suggested location and either accept it, edit it, or skip the row.'
                );
            }
        }

        $quantityExplicitZero = $this->isExplicitZeroImportValue($rawQuantity, $quantity);
        $priceExplicitZero = $this->isExplicitZeroImportValue($rawUnitPrice, $unitPrice);
        if ($quantity <= 0 || $unitPrice <= 0) {
            if ($quantity !== 0) {
                $this->recordImportModification(
                    $row,
                    'quantity',
                    $quantity,
                    0,
                    'Converted missing or invalid quantity to 0 for master-only import.'
                );
            }
            if ($unitPrice !== 0.0) {
                $this->recordImportModification(
                    $row,
                    'unit_price',
                    $unitPrice,
                    0,
                    'Converted missing or invalid cost/unit to 0 for master-only import.'
                );
            }

            $row['suggested_item']['quantity'] = 0;
            $row['suggested_item']['unit_price'] = 0.0;
            $row['action'] = 'create_master_only';

            if (!($quantityExplicitZero && $priceExplicitZero)) {
                $this->recordImportIssue(
                    $row,
                    'master_only_suggestion',
                    'quantity',
                    'warning',
                    'Quantity or cost/unit was missing or invalid, so the importer suggested a 0 quantity / 0 cost master-only import.',
                    'Accept the master-only suggestion, edit the quantity and cost, or skip the row.'
                );
            }
        }

        return $row;
    }

    private function applyImportGroupRules(array &$rows, bool $forCommit): void
    {
        $groups = [];
        foreach ($rows as $index => $row) {
            $supplierPartNumber = $this->normalizeString($row['suggested_item']['supplier_part_number'] ?? '');
            if ($supplierPartNumber === '') {
                continue;
            }

            if (!isset($groups[$supplierPartNumber])) {
                $groups[$supplierPartNumber] = [];
            }
            $groups[$supplierPartNumber][] = $index;
        }

        $existingCache = [];
        foreach ($groups as $supplierPartNumber => $indexes) {
            $referenceItem = null;
            foreach ($indexes as $index) {
                if (($rows[$index]['status'] ?? 'ready') === 'skip') {
                    continue;
                }
                $referenceItem = $rows[$index]['suggested_item'] ?? null;
                if ($referenceItem) {
                    break;
                }
            }

            foreach ($indexes as $index) {
                if (($rows[$index]['status'] ?? 'ready') === 'skip') {
                    continue;
                }

                $item = $rows[$index]['suggested_item'] ?? [];
                if ($referenceItem && !$this->areImportFieldsEquivalent($referenceItem['unit_of_measure'] ?? '', $item['unit_of_measure'] ?? '')) {
                    $this->recordImportIssue(
                        $rows[$index],
                        'supplier_part_conflict_unit',
                        'unit_of_measure',
                        $forCommit ? 'error' : 'warning',
                        'Rows with the same Supplier Part Number have different units of measure.',
                        'Make the unit match across duplicate supplier part numbers or split them into separate part numbers.'
                    );
                }

                if ($referenceItem && !$this->areImportFieldsEquivalent($referenceItem['name'] ?? '', $item['name'] ?? '')) {
                    $this->recordImportIssue(
                        $rows[$index],
                        'supplier_part_conflict_name',
                        'name',
                        $forCommit ? 'error' : 'warning',
                        'Rows with the same Supplier Part Number have conflicting descriptions.',
                        'Edit the descriptions so repeated supplier part numbers describe the same part.'
                    );
                }

                if ($referenceItem && !$this->areImportFieldsEquivalent($referenceItem['supplier_name'] ?? '', $item['supplier_name'] ?? '')) {
                    $this->recordImportIssue(
                        $rows[$index],
                        'supplier_part_conflict_manufacturer',
                        'supplier_name',
                        $forCommit ? 'error' : 'warning',
                        'Rows with the same Supplier Part Number have conflicting manufacturers.',
                        'Use the same manufacturer for duplicate supplier part numbers or correct the supplier part number.'
                    );
                }
            }

            if (!array_key_exists($supplierPartNumber, $existingCache)) {
                $existingLookup = $this->part->findBySupplierPartNumber($supplierPartNumber);
                $existingCache[$supplierPartNumber] = $existingLookup
                    ? ($this->part->findWithDetails((int)$existingLookup['id']) ?: $existingLookup)
                    : null;
            }

            $existingPart = $existingCache[$supplierPartNumber];
            if (!$existingPart) {
                continue;
            }

            foreach ($indexes as $index) {
                if (($rows[$index]['status'] ?? 'ready') === 'skip') {
                    continue;
                }

                $this->applyExistingPartContext($rows[$index], $existingPart, $forCommit);
            }
        }
    }

    private function applyExistingPartContext(array &$row, array $existingPart, bool $forCommit): void
    {
        $row['existing_part'] = [
            'id' => $existingPart['id'] ?? null,
            'fowler_part_number' => $existingPart['fowler_part_number'] ?? null,
            'name' => $existingPart['name'] ?? null,
            'supplier_name' => $existingPart['supplier_name'] ?? null,
            'unit_of_measure' => $existingPart['unit_of_measure'] ?? 'each',
        ];
        $row['action'] = ((int)($row['suggested_item']['quantity'] ?? 0) > 0) ? 'update_existing' : 'existing_noop';

        if (!$this->areImportFieldsEquivalent($row['suggested_item']['unit_of_measure'] ?? '', $existingPart['unit_of_measure'] ?? '')) {
            $this->recordImportIssue(
                $row,
                'existing_part_unit_conflict',
                'unit_of_measure',
                $forCommit ? 'error' : 'warning',
                'Supplier Part Number already exists with a different unit of measure.',
                'Change the unit to match the existing part or correct the supplier part number.'
            );
        }

        if (!$this->areImportFieldsEquivalent($row['suggested_item']['name'] ?? '', $existingPart['name'] ?? '')) {
            $this->recordImportIssue(
                $row,
                'existing_part_name_conflict',
                'name',
                $forCommit ? 'error' : 'warning',
                'Supplier Part Number already exists with a different description.',
                'Make the description match the existing part or correct the supplier part number.'
            );
        }

        $existingSupplier = $this->normalizeString($existingPart['supplier_name'] ?? '');
        $incomingSupplier = $this->normalizeString($row['suggested_item']['supplier_name'] ?? '');
        if ($existingSupplier !== '' && $incomingSupplier !== '' && !$this->areImportFieldsEquivalent($incomingSupplier, $existingSupplier)) {
            $this->recordImportIssue(
                $row,
                'existing_part_manufacturer_conflict',
                'supplier_name',
                $forCommit ? 'error' : 'warning',
                'Supplier Part Number already exists with a different manufacturer.',
                'Make the manufacturer match the existing part or correct the supplier part number.'
            );
        }
    }

    private function buildImportSummary(array $rows): array
    {
        $summary = [
            'total_rows' => count($rows),
            'ready_rows' => 0,
            'review_rows' => 0,
            'skipped_rows' => 0,
            'create_rows' => 0,
            'update_rows' => 0,
            'master_only_rows' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status'] ?? 'ready';
            if ($status === 'skip') {
                $summary['skipped_rows']++;
            } elseif ($status === 'review') {
                $summary['review_rows']++;
            } else {
                $summary['ready_rows']++;
            }

            if ($status === 'skip') {
                continue;
            }

            $action = $row['action'] ?? 'create';
            if ($action === 'create') {
                $summary['create_rows']++;
            } elseif ($action === 'update_existing') {
                $summary['update_rows']++;
            } elseif ($action === 'create_master_only' || $action === 'existing_noop') {
                $summary['master_only_rows']++;
            }
        }

        return $summary;
    }

    private function buildInvalidImportRow(int $index, string $message, string $remediation): array
    {
        $row = [
            'id' => sprintf('invalid:%d', $index + 1),
            'sheet' => 'Sheet1',
            'row' => $index + 1,
            'status' => 'skip',
            'action' => 'skip',
            'issues' => [],
            'modifications' => [],
            'existing_part' => null,
            'original' => [
                'supplier_part_number' => '',
                'description' => '',
                'supplier_name' => '',
                'location' => '',
                'unit_of_measure' => '',
                'quantity' => '',
                'unit_price' => '',
            ],
            'suggested_item' => [],
        ];

        $this->recordImportIssue($row, 'invalid_row', 'row', 'error', $message, $remediation);

        return $row;
    }

    private function recordImportIssue(
        array &$row,
        string $code,
        string $field,
        string $severity,
        string $message,
        string $remediation
    ): void {
        $row['issues'][] = [
            'code' => $code,
            'field' => $field,
            'severity' => $severity,
            'message' => $message,
            'remediation' => $remediation,
        ];

        if ($severity === 'error') {
            $this->escalateImportRowStatus($row, 'skip');
            return;
        }

        $this->escalateImportRowStatus($row, 'review');
    }

    private function recordImportModification(
        array &$row,
        string $field,
        $from,
        $to,
        string $reason
    ): void {
        if ((string)$from === (string)$to) {
            return;
        }

        $row['modifications'][] = [
            'field' => $field,
            'from' => $from,
            'to' => $to,
            'reason' => $reason,
        ];
    }

    private function escalateImportRowStatus(array &$row, string $status): void
    {
        $rank = [
            'ready' => 1,
            'review' => 2,
            'skip' => 3,
        ];

        $current = $row['status'] ?? 'ready';
        if (($rank[$status] ?? 1) > ($rank[$current] ?? 1)) {
            $row['status'] = $status;
        }
    }

    private function collectBlockingImportIssues(array $row): array
    {
        $issues = [];
        foreach (($row['issues'] ?? []) as $issue) {
            if (($issue['severity'] ?? '') === 'error') {
                $issues[] = $issue;
            }
        }

        if (empty($issues) && ($row['status'] ?? 'ready') === 'skip') {
            $issues[] = [
                'field' => 'row',
                'message' => 'Row skipped during import.',
                'remediation' => 'Review the row values and retry.',
            ];
        }

        return $issues;
    }

    private function buildImportErrorItem(array $row, array $issue): array
    {
        $item = $row['suggested_item'] ?? [];
        return [
            'sheet' => $row['sheet'] ?? '',
            'row' => $row['row'] ?? null,
            'supplier_part_number' => $item['supplier_part_number'] ?? ($row['original']['supplier_part_number'] ?? ''),
            'name' => $item['name'] ?? '',
            'supplier_name' => $item['supplier_name'] ?? '',
            'location' => $this->formatImportLocationForReport($item),
            'field' => $issue['field'] ?? 'row',
            'message' => $issue['message'] ?? 'Row skipped during import.',
            'remediation' => $issue['remediation'] ?? 'Review the row values and retry.',
        ];
    }

    private function buildImportRuntimeError(array $row, string $message, string $remediation): array
    {
        return $this->buildImportErrorItem($row, [
            'field' => 'row',
            'message' => $message,
            'remediation' => $remediation,
        ]);
    }

    private function buildLocationPayloadFromImportItem(array $item): array
    {
        return [
            'location_raw' => $this->deriveImportRawLocation($item),
            'location_aisle' => $this->normalizeString($item['location_aisle'] ?? ''),
            'location_shelf' => $this->normalizeString($item['location_shelf'] ?? ''),
            'location_bay' => $this->normalizeString($item['location_bay'] ?? ''),
            'location_alt' => $this->normalizeString($item['location_alt'] ?? ''),
        ];
    }

    private function deriveImportRawLocation(array $item): string
    {
        $locationAisle = $this->normalizeString($item['location_aisle'] ?? '');
        $locationShelf = $this->normalizeString($item['location_shelf'] ?? '');
        $locationBay = $this->normalizeString($item['location_bay'] ?? '');

        if ($locationAisle !== '' || $locationShelf !== '' || $locationBay !== '') {
            return implode('-', array_values(array_filter([
                $locationAisle,
                $locationShelf,
                $locationBay,
            ], static fn ($value) => $value !== '')));
        }

        return $this->normalizeString($item['location_raw'] ?? '');
    }

    private function normalizeImportLocation(string $rawLocation, string $alternateLocation): array
    {
        $raw = $this->normalizeString($rawLocation);
        $alt = $this->normalizeString($alternateLocation);
        if ($raw === '' && $alt === '') {
            return [
                'missing' => true,
                'location_raw' => '',
                'location_aisle' => '',
                'location_shelf' => '',
                'location_bay' => '',
                'location_alt' => '',
                'modifications' => [],
            ];
        }

        if ($raw === '') {
            return [
                'missing' => false,
                'location_raw' => '',
                'location_aisle' => '',
                'location_shelf' => '',
                'location_bay' => '',
                'location_alt' => $alt,
                'modifications' => [],
            ];
        }

        $structured = $this->cleanStructuredImportLocation($raw);
        if ($structured['is_structured']) {
            return [
                'missing' => false,
                'location_raw' => $structured['cleaned'],
                'location_aisle' => $structured['aisle'],
                'location_shelf' => $structured['shelf'],
                'location_bay' => $structured['bay'],
                'location_alt' => $alt,
                'modifications' => $structured['modifications'],
            ];
        }

        return [
            'missing' => false,
            'location_raw' => '',
            'location_aisle' => '',
            'location_shelf' => '',
            'location_bay' => '',
            'location_alt' => $this->mergeImportAltLocations($raw, $alt),
            'modifications' => [],
        ];
    }

    private function cleanStructuredImportLocation(string $value): array
    {
        $original = $this->normalizeString($value);
        $candidate = $original;
        $modifications = [];

        if ($original !== '' && preg_match('/^\d{3,}$/', $original)) {
            $aisle = substr($original, 0, -2);
            $shelf = substr($original, -2, 1);
            $bay = substr($original, -1);
            $cleaned = implode('-', [$aisle, $shelf, $bay]);

            return [
                'is_structured' => true,
                'cleaned' => $cleaned,
                'aisle' => $aisle,
                'shelf' => $shelf,
                'bay' => $bay,
                'modifications' => $cleaned !== $original ? [[
                    'from' => $original,
                    'to' => $cleaned,
                    'reason' => 'Expanded numeric location into aisle-shelf-bay format.',
                ]] : [],
            ];
        }

        if (preg_match('/^(?:shelf|shefl|self|shell)\s+(.+)$/i', $candidate, $matches)) {
            $candidate = $this->normalizeString($matches[1]);
            $modifications[] = [
                'from' => $original,
                'to' => $candidate,
                'reason' => 'Removed shelf prefix before normalizing location.',
            ];
        } elseif (preg_match('/^[A-Za-z]+\s+([A-Za-z0-9].*[0-9].*)$/', $candidate, $matches)
            && preg_match('/[-.\/, ]/', $matches[1])) {
            $candidate = $this->normalizeString($matches[1]);
            $modifications[] = [
                'from' => $original,
                'to' => $candidate,
                'reason' => 'Removed leading text before the structured location value.',
            ];
        }

        if (preg_match('/^([A-Za-z]+)\.(\d.+)$/', $candidate, $matches)) {
            $adjusted = $matches[1] . $matches[2];
            if ($adjusted !== $candidate) {
                $modifications[] = [
                    'from' => $candidate,
                    'to' => $adjusted,
                    'reason' => 'Joined letter prefix with the first numeric location token.',
                ];
                $candidate = $adjusted;
            }
        }

        $normalizedPunctuation = preg_replace('/[^A-Za-z0-9]+/', '-', $candidate);
        $normalizedPunctuation = preg_replace('/-+/', '-', (string)$normalizedPunctuation);
        $normalizedPunctuation = trim((string)$normalizedPunctuation, '-');
        if ($normalizedPunctuation !== '' && $normalizedPunctuation !== $candidate) {
            $modifications[] = [
                'from' => $candidate,
                'to' => $normalizedPunctuation,
                'reason' => 'Replaced punctuation with hyphens.',
            ];
            $candidate = $normalizedPunctuation;
        }

        $parts = array_values(array_filter(array_map('trim', explode('-', $candidate)), 'strlen'));
        if (count($parts) === 2) {
            $padded = implode('-', [$parts[0], $parts[1], '0']);
            $modifications[] = [
                'from' => $candidate,
                'to' => $padded,
                'reason' => 'Added 0 as the third location component.',
            ];
            $candidate = $padded;
            $parts = [$parts[0], $parts[1], '0'];
        }

        if (count($parts) === 3) {
            return [
                'is_structured' => true,
                'cleaned' => implode('-', $parts),
                'aisle' => $parts[0],
                'shelf' => $parts[1],
                'bay' => $parts[2],
                'modifications' => $modifications,
            ];
        }

        return [
            'is_structured' => false,
            'cleaned' => '',
            'aisle' => '',
            'shelf' => '',
            'bay' => '',
            'modifications' => [],
        ];
    }

    private function mergeImportAltLocations(string ...$values): string
    {
        $parts = [];
        foreach ($values as $value) {
            $clean = $this->normalizeString($value);
            if ($clean === '' || in_array($clean, $parts, true)) {
                continue;
            }
            $parts[] = $clean;
        }

        $merged = implode(' | ', $parts);
        return strlen($merged) > 255 ? substr($merged, 0, 255) : $merged;
    }

    private function areImportFieldsEquivalent($left, $right): bool
    {
        return strtolower($this->normalizeString($left)) === strtolower($this->normalizeString($right));
    }

    private function isExplicitZeroImportValue(string $rawValue, $numericValue): bool
    {
        if ($this->normalizeString($rawValue) === '') {
            return false;
        }

        return (float)$numericValue === 0.0;
    }

    private function formatImportLocationForReport(array $item): string
    {
        $alt = $this->normalizeString($item['location_alt'] ?? '');
        if ($alt !== '') {
            return $alt;
        }

        $parts = array_filter([
            $this->normalizeString($item['location_aisle'] ?? ''),
            $this->normalizeString($item['location_shelf'] ?? ''),
            $this->normalizeString($item['location_bay'] ?? ''),
        ], fn ($value) => $value !== '');

        return !empty($parts) ? implode('-', $parts) : '';
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
