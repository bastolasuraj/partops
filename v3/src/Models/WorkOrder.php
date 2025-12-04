<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class WorkOrder extends BaseModel
{
    protected static string $table = 'work_orders';

    public static function findByRef(string $ref): ?array
    {
        $sql = "SELECT * FROM work_orders WHERE external_ref = :ref LIMIT 1";
        return Database::queryOne($sql, ['ref' => $ref]);
    }

    public static function getOpen(): array
    {
        $sql = "SELECT wo.*, 
                    (SELECT COUNT(*) FROM inventory_moves im WHERE im.work_order_id = wo.id) as parts_count
                FROM work_orders wo 
                WHERE wo.status = 'open' 
                ORDER BY wo.opened_at DESC";
        return Database::query($sql);
    }

    public static function getOrCreate(string $externalRef, ?string $vehicleRef = null): int
    {
        $existing = self::findByRef($externalRef);
        if ($existing) {
            return (int)$existing['id'];
        }

        return self::create([
            'external_ref' => $externalRef,
            'vehicle_ref' => $vehicleRef,
            'status' => 'open',
            'opened_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function getPartsUsed(int $workOrderId): array
    {
        $sql = "SELECT im.*, 
                    p.anchor_slug, p.name as part_name,
                    t.name as technician_name
                FROM inventory_moves im
                JOIN parts p ON im.part_id = p.id
                LEFT JOIN technicians t ON im.technician_id = t.id
                WHERE im.work_order_id = :work_order_id
                ORDER BY im.created_at";
        return Database::query($sql, ['work_order_id' => $workOrderId]);
    }
}
