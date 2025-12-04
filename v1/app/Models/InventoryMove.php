<?php
/**
 * Inventory Move Model
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class InventoryMove extends Model
{
    protected string $table = 'inventory_moves';

    public function createMove(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (part_id, location_id, qty, direction, reason, work_order_id, work_order_ref, unit_number, technician_id, supplier_id, supplier_sku, price_at_tx, currency, core_charge_at_tx, core_rebate_expected, core_rebate_received, core_due_state, idempotency_key, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['part_id'],
            $data['location_id'],
            $data['qty'],
            $data['direction'],
            $data['reason'],
            $data['work_order_id'] ?? null,
            $data['work_order_ref'] ?? null,
            $data['unit_number'] ?? null,
            $data['technician_id'] ?? null,
            $data['supplier_id'] ?? null,
            $data['supplier_sku'] ?? null,
            $data['price_at_tx'] ?? null,
            $data['currency'] ?? 'USD',
            $data['core_charge_at_tx'] ?? null,
            $data['core_rebate_expected'] ?? null,
            $data['core_rebate_received'] ?? null,
            $data['core_due_state'] ?? 'none',
            $data['idempotency_key'] ?? null,
            $data['notes'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getRecent(int $limit = 50): array
    {
        $sql = "SELECT im.*, p.name as part_name, p.anchor_slug, 
                       l.aisle, l.shelf, l.bay
                FROM {$this->table} im
                JOIN parts p ON im.part_id = p.id
                JOIN locations l ON im.location_id = l.id
                ORDER BY im.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function searchWorkOrders(string $query): array
    {
        $sql = "SELECT DISTINCT work_order_ref, unit_number
                FROM {$this->table}
                WHERE work_order_ref IS NOT NULL
                AND work_order_ref != ''
                AND work_order_ref LIKE ?
                ORDER BY work_order_ref DESC
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['%' . $query . '%']);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getWorkOrderParts(string $workOrderRef): array
    {
        $sql = "SELECT 
                    im.part_id,
                    im.location_id,
                    p.anchor_slug,
                    l.aisle,
                    l.shelf,
                    l.bay,
                    im.unit_number,
                    SUM(CASE WHEN im.direction = 'out' AND im.reason = 'checkout' THEN im.qty ELSE 0 END) as total_out,
                    SUM(CASE WHEN im.direction = 'in' AND im.reason = 'tech_return' THEN im.qty ELSE 0 END) as total_returned
                FROM {$this->table} im
                JOIN parts p ON im.part_id = p.id
                JOIN locations l ON im.location_id = l.id
                WHERE im.work_order_ref = ?
                GROUP BY im.part_id, im.location_id, p.anchor_slug, l.aisle, l.shelf, l.bay, im.unit_number
                HAVING (total_out - total_returned) > 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$workOrderRef]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
