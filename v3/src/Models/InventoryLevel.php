<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class InventoryLevel extends BaseModel
{
    protected static string $table = 'inventory_levels';

    public static function getByPart(int $partId): array
    {
        $sql = "SELECT il.*, l.aisle, l.shelf, l.bay, l.bin,
                    CONCAT(l.aisle, ' / ', l.shelf, ' / ', l.bay) as location_formatted
                FROM inventory_levels il 
                JOIN locations l ON il.location_id = l.id 
                WHERE il.part_id = :part_id";
        return Database::query($sql, ['part_id' => $partId]);
    }

    public static function getByLocation(int $locationId): array
    {
        $sql = "SELECT il.*, p.anchor_slug, p.name as part_name,
                    (SELECT pn.value FROM part_numbers pn WHERE pn.part_id = p.id AND pn.is_primary = true LIMIT 1) as part_number
                FROM inventory_levels il 
                JOIN parts p ON il.part_id = p.id 
                WHERE il.location_id = :location_id";
        return Database::query($sql, ['location_id' => $locationId]);
    }

    public static function findByPartAndLocation(int $partId, int $locationId): ?array
    {
        $sql = "SELECT * FROM inventory_levels WHERE part_id = :part_id AND location_id = :location_id LIMIT 1";
        return Database::queryOne($sql, ['part_id' => $partId, 'location_id' => $locationId]);
    }

    public static function adjustStock(int $partId, int $locationId, int $quantity, bool $forUpdate = true): bool
    {
        $lock = $forUpdate ? 'FOR UPDATE' : '';
        
        $existing = Database::queryOne(
            "SELECT * FROM inventory_levels WHERE part_id = :part_id AND location_id = :location_id $lock",
            ['part_id' => $partId, 'location_id' => $locationId]
        );

        if ($existing) {
            $newQty = (int)$existing['on_hand'] + $quantity;
            if ($newQty < 0) return false;
            
            return Database::execute(
                "UPDATE inventory_levels SET on_hand = :qty, updated_at = NOW() WHERE id = :id",
                ['qty' => $newQty, 'id' => $existing['id']]
            ) > 0;
        } else {
            if ($quantity < 0) return false;
            
            return Database::execute(
                "INSERT INTO inventory_levels (part_id, location_id, on_hand, reserved, created_at, updated_at) 
                 VALUES (:part_id, :location_id, :qty, 0, NOW(), NOW())",
                ['part_id' => $partId, 'location_id' => $locationId, 'qty' => $quantity]
            ) > 0;
        }
    }

    public static function getTotalStock(int $partId): int
    {
        $sql = "SELECT COALESCE(SUM(on_hand), 0) as total FROM inventory_levels WHERE part_id = :part_id";
        $result = Database::queryOne($sql, ['part_id' => $partId]);
        return (int)($result['total'] ?? 0);
    }

    public static function getAvailable(int $partId, int $locationId): int
    {
        $sql = "SELECT COALESCE(on_hand - reserved, 0) as available 
                FROM inventory_levels WHERE part_id = :part_id AND location_id = :location_id";
        $result = Database::queryOne($sql, ['part_id' => $partId, 'location_id' => $locationId]);
        return (int)($result['available'] ?? 0);
    }
}
