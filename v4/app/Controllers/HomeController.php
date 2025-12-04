<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\Part;
use App\Models\WorkOrder;

class HomeController
{
    public function index(): void
    {
        View::redirect('/dashboard');
    }

    public function dashboard(): void
    {
        $partModel = new Part();
        $woModel = new WorkOrder();

        $data = [
            'lowStockParts' => $partModel->getLowStock(),
            'recentWorkOrders' => $woModel->getRecent(10),
            'totalParts' => count($partModel->all()),
        ];

        View::render('home/dashboard', $data);
    }
}
