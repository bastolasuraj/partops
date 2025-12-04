<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;
use PartOps\Services\Auth;

class InventoryMove extends BaseModel
{
    protected static string $table = 'inventory_moves';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    public const REASON_RECEIVE = 'receive';
    public const REASON_CHECKOUT = 'checkout';
    public const REASON_RETURN = 'return';
    public const REASON_CORE_RETURN = 'core_return';
    public const REASON_ADJUST = 'adjust';

    public const CORE_STATE_NONE = 'none';
    public const CORE_STATE_DUE = 'due';
    public const CORE_STATE_SENT = 'sent';
    public const CORE_STATE_REBATED = 'rebated';

    public static function record(array $data): ?int
    {
        $data['created_by'] = Auth::id();
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return self::create($data);
    }

    public static function getByPart(int $partId, int $limit = 50): array
    {
        $sql = "SELECT im.*, 
                    l.aisle, l.shelf, l.bay,
                    t.name as technician_name,
                    wo.external_ref as work_order_ref,
                    s.name as supplier_name,
                    u.username as created_by_name
                FROM inventory_moves im
                LEFT JOIN locations l ON im.location_id = l.id
                LEFT JOIN technicians t ON im.technician_id = t.id
                LEFT JOIN work_orders wo ON im.work_order_id = wo.id
                LEFT JOIN suppliers s ON im.supplier_id = s.id
                LEFT JOIN users u ON im.created_by = u.id
                WHERE im.part_id = :part_id
                ORDER BY im.created_at DESC
                LIMIT :limit";
        return Database::query($sql, ['part_id' => $partId, 'limit' => $limit]);
    }

    public static function getRecent(int $limit = 100): array
    {
        $sql = "SELECT im.*, 
                    p.anchor_slug, p.name as part_name,
                    l.aisle, l.shelf, l.bay,
                    t.name as technician_name,
                    wo.external_ref as work_order_ref,
                    u.username as created_by_name
                FROM inventory_moves im
                JOIN parts p ON im.part_id = p.id
                LEFT JOIN locations l ON im.location_id = l.id
                LEFT JOIN technicians t ON im.technician_id = t.id
                LEFT JOIN work_orders wo ON im.work_order_id = wo.id
                LEFT JOIN users u ON im.created_by = u.id
                ORDER BY im.created_at DESC
                LIMIT :limit";
        return Database::query($sql, ['limit' => $limit]);
    }

    public static function getPendingCoreReturns(): array
    {
        $sql = "SELECT im.*, 
                    p.anchor_slug, p.name as part_name,
                    s.name as supplier_name
                FROM inventory_moves im
                JOIN parts p ON im.part_id = p.id
                LEFT JOIN suppliers s ON im.supplier_id = s.id
                WHERE im.core_due_state IN ('due', 'sent') 
                AND im.reason = 'receive'
                ORDER BY im.created_at";
        return Database::query($sql);
    }
}
