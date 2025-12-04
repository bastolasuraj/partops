<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Models\Part;
use App\Models\WorkOrder;
use App\Models\PartSupplierNumber;
use App\Models\Technician;

class CheckoutController
{
    public function index(): void
    {
        $sql = "SELECT pc.*, p.fowler_part_number, p.name as part_name, 
                       wo.wo_number, t.name as technician_name
                FROM part_checkouts pc
                JOIN parts p ON pc.part_id = p.id
                JOIN work_orders wo ON pc.work_order_id = wo.id
                JOIN technicians t ON pc.technician_id = t.id
                ORDER BY pc.created_at DESC
                LIMIT 50";
        $checkouts = Database::fetchAll($sql);
        View::render('checkouts/index', ['checkouts' => $checkouts]);
    }

    public function create(): void
    {
        $partModel = new Part();
        $parts = $partModel->getWithInventory();

        // Efficiently build the supplier map with a single query
        // Combine data from master list (part_supplier_numbers) and transaction history (part_checkins)
        // And calculate stock per supplier part
        $sql = "SELECT DISTINCT 
                    combined.part_id, 
                    combined.supplier_id, 
                    combined.supplier_part_number, 
                    s.name as supplier_name,
                    (
                        COALESCE((SELECT SUM(quantity) FROM part_checkins WHERE supplier_part_number = combined.supplier_part_number AND part_id = combined.part_id), 0) -
                        COALESCE((SELECT SUM(quantity) FROM part_checkouts WHERE supplier_part_number = combined.supplier_part_number AND part_id = combined.part_id), 0) -
                        COALESCE((SELECT SUM(quantity) FROM returns WHERE supplier_part_number = combined.supplier_part_number AND part_id = combined.part_id AND is_core_charge = 0), 0)
                    ) as stock
                FROM (
                    SELECT part_id, supplier_id, supplier_part_number 
                    FROM part_supplier_numbers
                    UNION
                    SELECT part_id, supplier_id, supplier_part_number 
                    FROM part_checkins 
                    WHERE supplier_id IS NOT NULL 
                    AND supplier_part_number IS NOT NULL AND supplier_part_number != ''
                ) as combined
                JOIN suppliers s ON combined.supplier_id = s.id
                ORDER BY combined.part_id, s.name, combined.supplier_part_number";
        
        $allSupplierNumbers = Database::fetchAll($sql);
        
        $supplierMap = [];
        foreach ($allSupplierNumbers as $sn) {
            $supplierMap[(int)$sn['part_id']][] = $sn;
        }

        $techs = (new Technician())->getActive();
        
        View::render('checkouts/create', [
            'parts' => $parts,
            'supplierMap' => $supplierMap,
            'technicians' => $techs,
        ]);
    }

    public function store(): void
    {
        try {
            $woNumber = strtoupper(trim($_POST['work_order_number'] ?? ''));
            $techName = trim($_POST['technician_name'] ?? '');
            $unitNumber = trim($_POST['unit_number'] ?? '');

            if (empty($woNumber)) {
                throw new \InvalidArgumentException('Work order number is required.');
            }
            if (empty($unitNumber)) {
                throw new \InvalidArgumentException('Unit number is required.');
            }

            $techRow = $this->findTechnicianByName($techName);
            if (!$techRow) {
                throw new \InvalidArgumentException('Technician is required and must be valid.');
            }

            $woModel = new WorkOrder();
            $wo = $woModel->findByNumber($woNumber);
            if (!$wo) {
                $this->createWorkOrder($woNumber, (int)$techRow['id'], $unitNumber);
                $wo = $woModel->findByNumber($woNumber);
            }

            if (isset($_POST['parts']) && is_array($_POST['parts'])) {
                $psnModel = new PartSupplierNumber();
                foreach ($_POST['parts'] as $partData) {
                    $partId = (int)($partData['part_id'] ?? 0);
                    $quantity = (int)($partData['quantity'] ?? 0);

                    if ($partId === 0 || $quantity === 0) continue;

                    $suppliers = $psnModel->getByPart($partId);
                    $supplierPartNumber = $partData['supplier_part_number'] ?? null;

                    if (count($suppliers) === 1 && empty($supplierPartNumber)) {
                        $supplierPartNumber = $suppliers[0]['supplier_part_number'];
                    } elseif (count($suppliers) > 1 && empty($supplierPartNumber)) {
                        throw new \InvalidArgumentException("Part #{$partId} requires a supplier part selection as it has multiple options.");
                    }

                    // Server-side Stock Validation
                    $stockSql = "SELECT 
                        (
                            COALESCE((SELECT SUM(quantity) FROM part_checkins WHERE part_id = ? AND supplier_part_number = ?), 0) -
                            COALESCE((SELECT SUM(quantity) FROM part_checkouts WHERE part_id = ? AND supplier_part_number = ?), 0) -
                            COALESCE((SELECT SUM(quantity) FROM returns WHERE part_id = ? AND supplier_part_number = ? AND is_core_charge = 0), 0)
                        ) as stock";
                    
                    // Note: supplier_part_number can be null in some old data, but here we expect it if checking specific stock.
                    // If it's null (generic part), we should probably check generic stock, but the UI forces supplier selection now.
                    $currentStock = (int)Database::fetch($stockSql, [
                        $partId, $supplierPartNumber,
                        $partId, $supplierPartNumber,
                        $partId, $supplierPartNumber
                    ])['stock'];

                    if ($quantity > $currentStock) {
                        throw new \Exception("Insufficient stock for part #{$partId} (Supplier Part: {$supplierPartNumber}). Requested: {$quantity}, Available: {$currentStock}.");
                    }

                    $checkoutData = [
                        (int)$wo['id'], (int)$techRow['id'], $unitNumber, 
                        $partId, $supplierPartNumber, $quantity
                    ];

                    $sql = "INSERT INTO part_checkouts (work_order_id, technician_id, unit_number, part_id, supplier_part_number, quantity) VALUES (?, ?, ?, ?, ?, ?)";
                    Database::query($sql, $checkoutData);
                }
            }

            $_SESSION['success'] = 'Parts checked out successfully!';
            View::redirect('/work-orders');

        } catch (\Exception $e) {
            Logger::logApp('Failed to checkout parts: ' . $e->getMessage(), 'ERROR', ['post_data' => $_POST]);
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            View::redirect('/checkouts/create');
        }
    }

    private function createWorkOrder(string $woNumber, ?int $technicianId, string $unitNumber): void
    {
        $sql = "INSERT INTO work_orders (wo_number, technician_id, unit_number) VALUES (?, ?, ?)";
        Database::query($sql, [$woNumber, $technicianId, $unitNumber]);
    }

    
    private function findTechnicianByName(string $name): ?array
    {
        if ($name === '') {
            return null;
        }
        $sql = "SELECT * FROM technicians WHERE name = ? LIMIT 1";
        return Database::fetch($sql, [$name]);
    }
}
