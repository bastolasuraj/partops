<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Part;
use App\Models\WorkOrder;
use App\Models\InventoryMove;
use App\Models\CoreLiability;

/**
 * Home Controller - Dashboard
 * This file should be moved to: app/Controllers/HomeController.php
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $partModel = new Part();
        $woModel = new WorkOrder();
        $moveModel = new InventoryMove();
        $coreModel = new CoreLiability();

        // Get dashboard stats
        $stats = [
            'total_parts' => $partModel->count(['is_active' => 1]),
            'low_stock' => count($partModel->getLowStock()),
            'open_work_orders' => $woModel->count(['status' => 'open']),
            'pending_cores' => count($coreModel->getPending()),
        ];

        // Get recent activity
        $recentMoves = $moveModel->getRecent(10);

        // Get low stock items
        $lowStock = $partModel->getLowStock();

        // Get pending core returns
        $pendingCores = $coreModel->getPending();

        $this->renderWithLayout('home/index', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'recentMoves' => $recentMoves,
            'lowStock' => array_slice($lowStock, 0, 5),
            'pendingCores' => array_slice($pendingCores, 0, 5),
        ]);
    }
}
