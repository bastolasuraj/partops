<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Services\Database;
use PartOps\Models\InventoryMove;

class ReportsController extends BaseController
{
    public function index(): void
    {
        $stats = $this->getStats();
        
        $this->render('reports.index', [
            'stats' => $stats,
            'pageTitle' => 'Reports & Alerts'
        ]);
    }

    public function lowStock(): void
    {
        $sql = "SELECT p.*, 
                    (SELECT pn.value FROM part_numbers pn WHERE pn.part_id = p.id AND pn.is_primary = true LIMIT 1) as part_number,
                    COALESCE(SUM(il.on_hand), 0) as total_stock
                FROM parts p
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                WHERE p.is_active = true
                GROUP BY p.id
                HAVING COALESCE(SUM(il.on_hand), 0) < 5
                ORDER BY total_stock, p.name";
        
        $parts = Database::query($sql);

        $this->render('reports.low_stock', [
            'parts' => $parts,
            'pageTitle' => 'Low Stock Report'
        ]);
    }

    public function coreLiabilities(): void
    {
        $pendingCores = InventoryMove::getPendingCoreReturns();
        
        $totalDue = array_sum(array_column($pendingCores, 'core_charge_at_tx'));
        $totalExpectedRebate = array_sum(array_column($pendingCores, 'core_rebate_expected'));

        $this->render('reports.core_liabilities', [
            'pendingCores' => $pendingCores,
            'totalDue' => $totalDue,
            'totalExpectedRebate' => $totalExpectedRebate,
            'pageTitle' => 'Core Liabilities'
        ]);
    }

    public function technicianUsage(): void
    {
        $sql = "SELECT t.id, t.name, t.email,
                    COUNT(CASE WHEN im.reason = 'checkout' THEN 1 END) as checkouts,
                    COUNT(CASE WHEN im.reason = 'return' THEN 1 END) as returns,
                    SUM(CASE WHEN im.reason = 'checkout' THEN im.qty ELSE 0 END) as parts_used
                FROM technicians t
                LEFT JOIN inventory_moves im ON t.id = im.technician_id
                    AND im.created_at > NOW() - INTERVAL 30 DAY
                WHERE t.is_active = true
                GROUP BY t.id, t.name, t.email
                ORDER BY parts_used IS NULL, parts_used DESC";

        $usage = Database::query($sql);

        $this->render('reports.technician_usage', [
            'usage' => $usage,
            'pageTitle' => 'Technician Usage (30 days)'
        ]);
    }

    public function auditLog(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT al.*, u.username 
                FROM audit_log al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $logs = Database::query($sql, ['limit' => $limit, 'offset' => $offset]);
        
        $countSql = "SELECT COUNT(*) as total FROM audit_log";
        $total = Database::queryOne($countSql)['total'];

        $this->render('reports.audit_log', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => ceil($total / $limit),
            'pageTitle' => 'Audit Log'
        ]);
    }

    private function getStats(): array
    {
        $lowStockSql = "SELECT COUNT(DISTINCT p.id) as count 
                        FROM parts p 
                        LEFT JOIN inventory_levels il ON p.id = il.part_id 
                        WHERE p.is_active = true
                        GROUP BY p.id
                        HAVING COALESCE(SUM(il.on_hand), 0) < 5";
        $lowStockResult = Database::query($lowStockSql);

        $pendingCoresSql = "SELECT COUNT(*) as count, COALESCE(SUM(core_charge_at_tx), 0) as total 
                           FROM inventory_moves 
                           WHERE core_due_state IN ('due', 'sent') AND reason = 'receive'";
        $pendingCores = Database::queryOne($pendingCoresSql);

        $agingInventorySql = "SELECT COUNT(DISTINCT part_id) as count 
                              FROM inventory_moves 
                              WHERE reason = 'receive' 
                              AND created_at < NOW() - INTERVAL 90 DAY";
        $agingInventory = Database::queryOne($agingInventorySql);

        $weeklyUsageSql = "SELECT COALESCE(SUM(qty), 0) as count 
                           FROM inventory_moves 
                           WHERE reason = 'checkout' 
                           AND created_at > NOW() - INTERVAL 7 DAY";
        $weeklyUsage = Database::queryOne($weeklyUsageSql);

        return [
            'lowStock' => count($lowStockResult),
            'pendingCores' => (int)($pendingCores['count'] ?? 0),
            'pendingCoresValue' => (float)($pendingCores['total'] ?? 0),
            'agingInventory' => (int)($agingInventory['count'] ?? 0),
            'weeklyUsage' => (int)($weeklyUsage['count'] ?? 0)
        ];
    }
}
