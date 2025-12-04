<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Part;
use App\Models\Supplier;
use App\Models\PartSupplierNumber;

class PartController
{
    private Part $model;
    public function __construct()
    {
        $this->model = new Part();
    }

    public function index(): void
    {
        $parts = $this->model->getWithInventory();
        View::render('parts/index', ['parts' => $parts]);
    }

    public function create(): void
    {
        View::render('parts/create');
    }

    public function store(): void
    {
        $data = [
            'fowler_part_number' => $_POST['fowler_part_number'] ?? '',
            'name' => $_POST['name'] ?? '',
            'location_aisle' => $_POST['aisle'] ?? null,
            'location_shelf' => $_POST['shelf'] ?? null,
            'location_bay' => $_POST['bay'] ?? null,
            'low_stock_threshold' => !empty($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : null,
            'notes' => $_POST['notes'] ?? null,
        ];

        $partId = $this->model->create($data);
        $_SESSION['success'] = 'Part created successfully!';
        View::redirect('/parts');
    }

    public function show(string $id): void
    {
        $part = $this->model->getDetailed((int)$id);
        if (!$part) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $inventory = $this->model->getInventoryLevel((int)$id);
        $supplierNumbers = [];
        View::render('parts/show', [
            'part' => $part,
            'inventory' => $inventory,
            'supplierNumbers' => $supplierNumbers
        ]);
    }

    public function edit(string $id): void
    {
        $part = $this->model->find((int)$id);
        if (!$part) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        View::render('parts/edit', ['part' => $part]);
    }

    public function update(string $id): void
    {
        $data = [
            'fowler_part_number' => $_POST['fowler_part_number'] ?? '',
            'name' => $_POST['name'] ?? '',
            'location_aisle' => $_POST['aisle'] ?? null,
            'location_shelf' => $_POST['shelf'] ?? null,
            'location_bay' => $_POST['bay'] ?? null,
            'low_stock_threshold' => !empty($_POST['low_stock_threshold']) ? (int)$_POST['low_stock_threshold'] : null,
            'notes' => $_POST['notes'] ?? null,
        ];

        $this->model->update((int)$id, $data);
        $_SESSION['success'] = 'Part updated successfully!';
        View::redirect('/parts');
    }

    public function delete(string $id): void
    {
        $this->model->delete((int)$id);
        $_SESSION['success'] = 'Part deleted successfully!';
        View::redirect('/parts');
    }

    public function search(): void
    {
        $query = $_GET['q'] ?? '';
        $parts = $this->model->search($query);
        View::json(['parts' => $parts]);
    }

    private function cleanSupplierNumbers(array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            $supplierId = !empty($row['supplier_id']) ? (int)$row['supplier_id'] : null;
            $number = isset($row['supplier_part_number']) ? trim($row['supplier_part_number']) : '';
            if ($supplierId && $number !== '') {
                $clean[] = [
                    'supplier_id' => $supplierId,
                    'supplier_part_number' => $number,
                ];
            }
        }
        return $clean;
    }
}
