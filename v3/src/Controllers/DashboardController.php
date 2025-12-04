<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Models\Part;
use PartOps\Models\InventoryMove;
use PartOps\Models\InventoryLevel;
use PartOps\Services\Database;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $stats = $this->getStats();
        $recentMoves = InventoryMove::getRecent(10);
        
        $this->render('dashboard.index', [
            'stats' => $stats,
            'recentMoves' => $recentMoves,
            'pageTitle' => 'Dashboard'
        ]);
    }

    private function getStats(): array
    {
        $lowStockSql = "SELECT COUNT(DISTINCT p.id) as count 
                        FROM parts p 
                        JOIN inventory_levels il ON p.id = il.part_id 
                        WHERE il.on_hand < 5 AND p.is_active = true";
        $lowStock = Database::queryOne($lowStockSql);

        $pendingCoresSql = "SELECT COUNT(*) as count, COALESCE(SUM(core_charge_at_tx), 0) as total 
                           FROM inventory_moves 
                           WHERE core_due_state IN ('due', 'sent') AND reason = 'receive'";
        $pendingCores = Database::queryOne($pendingCoresSql);

        $agingInventorySql = "SELECT COUNT(*) as count 
                              FROM inventory_moves 
                              WHERE reason = 'receive' 
                              AND created_at < NOW() - INTERVAL 90 DAY
                              AND part_id NOT IN (SELECT part_id FROM inventory_moves WHERE reason = 'checkout')";
        $agingInventory = Database::queryOne($agingInventorySql);

        $weeklyUsageSql = "SELECT COUNT(*) as count 
                           FROM inventory_moves 
                           WHERE reason = 'checkout' 
                           AND created_at > NOW() - INTERVAL 7 DAY";
        $weeklyUsage = Database::queryOne($weeklyUsageSql);

        return [
            'lowStock' => (int)($lowStock['count'] ?? 0),
            'pendingCores' => (int)($pendingCores['count'] ?? 0),
            'pendingCoresValue' => (float)($pendingCores['total'] ?? 0),
            'agingInventory' => (int)($agingInventory['count'] ?? 0),
            'weeklyUsage' => (int)($weeklyUsage['count'] ?? 0)
        ];
    }
}
