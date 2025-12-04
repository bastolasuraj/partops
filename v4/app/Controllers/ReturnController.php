<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Database;
use App\Models\Supplier;

class ReturnController
{
    public function index(): void
    {
        $sql = "SELECT * FROM v_outstanding_cores ORDER BY created_at DESC";
        $outstandingCores = Database::fetchAll($sql);
        View::render('returns/index', ['outstandingCores' => $outstandingCores]);
    }

    public function supplierReturn(): void
    {
        $supplierModel = new Supplier();
        $suppliers = $supplierModel->getActive();
        View::render('returns/supplier', ['suppliers' => $suppliers]);
    }

    public function storeSupplierReturn(): void
    {
        $data = [
            'supplier_id' => (int)$_POST['supplier_id'],
            'supplier_part_number' => $_POST['supplier_part_number'],
            'part_id' => !empty($_POST['part_id']) ? (int)$_POST['part_id'] : null,
            'quantity' => (int)$_POST['quantity'],
            'notes' => $_POST['notes'] ?? null,
            'is_core_charge' => isset($_POST['is_core_charge']) ? 1 : 0,
            'core_charge_amount' => !empty($_POST['core_charge_amount']) ? (float)$_POST['core_charge_amount'] : null,
            'expected_rebate' => !empty($_POST['expected_rebate']) ? (float)$_POST['expected_rebate'] : null,
            'rebate_received' => !empty($_POST['rebate_received']) ? (float)$_POST['rebate_received'] : null,
        ];

        $sql = "INSERT INTO returns (supplier_id, supplier_part_number, part_id, quantity, notes, is_core_charge, core_charge_amount, expected_rebate, rebate_received) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        Database::query($sql, array_values($data));

        $_SESSION['success'] = 'Return processed successfully!';
        View::redirect('/returns/supplier');
    }
}
