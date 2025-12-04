<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\WorkOrder;
use App\Models\Technician;

class WorkOrderController
{
    private WorkOrder $model;

    public function __construct()
    {
        $this->model = new WorkOrder();
    }

    public function index(): void
    {
        $query = $_GET['q'] ?? '';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $workOrders = $this->model->search($query, $dateFrom, $dateTo);

        if (isset($_GET['ajax'])) {
            View::render('work-orders/partials/rows', ['workOrders' => $workOrders], false);
            return;
        }

        View::render('work-orders/index', [
            'workOrders' => $workOrders,
            'q' => $query,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }

    public function store(): void
    {
        $data = [
            'wo_number' => $_POST['wo_number'] ?? '',
            'technician_id' => !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null,
            'unit_number' => $_POST['unit_number'] ?? null,
        ];

        $this->model->create($data);
        $_SESSION['success'] = 'Work order created successfully!';
        View::redirect('/work-orders');
    }

    public function show(string $id): void
    {
        $workOrder = $this->model->find((int)$id);
        if (!$workOrder) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $parts = $this->model->getWithParts((int)$id);
        View::render('work-orders/show', ['workOrder' => $workOrder, 'parts' => $parts]);
    }

    public function getParts(string $id): void
    {
        $parts = $this->model->getWithParts((int)$id);
        View::json(['parts' => $parts]);
    }
}
