<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class Technician extends BaseModel
{
    protected static string $table = 'technicians';

    public static function getActive(): array
    {
        $sql = "SELECT t.*, 
                    (SELECT COUNT(*) FROM inventory_moves im WHERE im.technician_id = t.id AND im.reason = 'checkout') as total_checkouts,
                    (SELECT COUNT(*) FROM inventory_moves im WHERE im.technician_id = t.id AND im.reason = 'checkout' 
                     AND NOT EXISTS (SELECT 1 FROM inventory_moves im2 WHERE im2.part_id = im.part_id 
                                    AND im2.work_order_id = im.work_order_id AND im2.reason = 'return' 
                                    AND im2.created_at > im.created_at)) as active_checkouts
                FROM technicians t 
                WHERE t.is_active = true 
                ORDER BY t.name";
        return Database::query($sql);
    }

    public static function search(string $query): array
    {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT * FROM technicians 
                WHERE (name LIKE :q1 OR email LIKE :q2) AND is_active = true 
                ORDER BY name";
        return Database::query($sql, ['q1' => $searchTerm, 'q2' => $searchTerm]);
    }
}
