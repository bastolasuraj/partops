<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * InventoryMove Model - Audit trail for all inventory movements
 * This file should be moved to: app/Models/InventoryMove.php
 */
class InventoryMove extends Model
{
    protected string $table = 'inventory_moves';
    protected array $fillable = [
        'part_id',
        'part_number_id',
        'location_id',
        'qty',
        'direction',
        'reason',
        'work_order_id',
        'technician_id',
        'supplier_id',
        'supplier_sku',
        'po_number',
        'price_at_tx',
        'currency',
        'core_charge_at_tx',
        'core_rebate_expected',
        'core_rebate_received',
        'core_due_state',
        'idempotency_key',
        'user_id',
        'notes'
    ];
    protected bool $timestamps = false;

    /**
     * Check if idempotency key already exists
     */
    public function idempotencyKeyExists(string $key): bool
    {
        $result = $this->findBy('idempotency_key', $key);
        return $result !== null;
    }

    /**
     * Record a receiving transaction (new parts)
     */
    public function recordReceiveNew(array $data): int
    {
        $data['direction'] = 'in';
        $data['reason'] = 'receive_new';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Record a work order return (unused parts back)
     */
    public function recordWoReturn(array $data): int
    {
        $data['direction'] = 'in';
        $data['reason'] = 'receive_wo_return';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Record a checkout transaction
     */
    public function recordCheckout(array $data): int
    {
        $data['direction'] = 'out';
        $data['reason'] = 'checkout';
        $data['qty'] = abs($data['qty']) * -1; // Ensure negative
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Record a standard return
     */
    public function recordStandardReturn(array $data): int
    {
        $data['direction'] = 'in';
        $data['reason'] = 'return_standard';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Record a core return
     */
    public function recordCoreReturn(array $data): int
    {
        $data['direction'] = 'in';
        $data['reason'] = 'return_core';
        $data['core_due_state'] = 'due';
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Get recent movements
     */
    public function getRecent(int $limit = 50): array
    {
        $sql = "
            SELECT im.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   l.aisle, l.shelf, l.bay,
                   wo.wo_number,
                   t.name AS technician_name,
                   s.name AS supplier_name,
                   u.username AS user_name
            FROM inventory_moves im
            JOIN parts p ON p.id = im.part_id
            LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
            JOIN locations l ON l.id = im.location_id
            LEFT JOIN work_orders wo ON wo.id = im.work_order_id
            LEFT JOIN technicians t ON t.id = im.technician_id
            LEFT JOIN suppliers s ON s.id = im.supplier_id
            LEFT JOIN users u ON u.id = im.user_id
            ORDER BY im.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get movements for a part
     */
    public function getByPart(int $partId, int $limit = 50): array
    {
        $sql = "
            SELECT im.*, 
                   l.aisle, l.shelf, l.bay,
                   wo.wo_number,
                   t.name AS technician_name,
                   s.name AS supplier_name
            FROM inventory_moves im
            JOIN locations l ON l.id = im.location_id
            LEFT JOIN work_orders wo ON wo.id = im.work_order_id
            LEFT JOIN technicians t ON t.id = im.technician_id
            LEFT JOIN suppliers s ON s.id = im.supplier_id
            WHERE im.part_id = :part_id
            ORDER BY im.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':part_id', $partId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get movements for a work order
     */
    public function getByWorkOrder(int $workOrderId): array
    {
        $sql = "
            SELECT im.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   l.aisle, l.shelf, l.bay,
                   t.name AS technician_name
            FROM inventory_moves im
            JOIN parts p ON p.id = im.part_id
            LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
            JOIN locations l ON l.id = im.location_id
            LEFT JOIN technicians t ON t.id = im.technician_id
            WHERE im.work_order_id = :work_order_id
            ORDER BY im.created_at DESC
        ";
        
        return $this->query($sql, ['work_order_id' => $workOrderId]);
    }

    /**
     * Get movements with pending core returns
     */
    public function getPendingCoreReturns(): array
    {
        $sql = "
            SELECT im.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   s.name AS supplier_name
            FROM inventory_moves im
            JOIN parts p ON p.id = im.part_id
            LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
            LEFT JOIN suppliers s ON s.id = im.supplier_id
            WHERE im.core_due_state = 'due'
            ORDER BY im.created_at ASC
        ";
        
        return $this->query($sql);
    }

    /**
     * Get movements by reason
     */
    public function getByReason(string $reason, int $limit = 50): array
    {
        $sql = "
            SELECT im.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   l.aisle, l.shelf, l.bay
            FROM inventory_moves im
            JOIN parts p ON p.id = im.part_id
            LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
            JOIN locations l ON l.id = im.location_id
            WHERE im.reason = :reason
            ORDER BY im.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':reason', $reason);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
