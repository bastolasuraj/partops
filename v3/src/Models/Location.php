<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class Location extends BaseModel
{
    protected static string $table = 'locations';

    public static function getActive(): array
    {
        $sql = "SELECT l.*, 
                    (SELECT COUNT(*) FROM inventory_levels il WHERE il.location_id = l.id) as items_count,
                    (SELECT SUM(il.on_hand) FROM inventory_levels il WHERE il.location_id = l.id) as total_stock
                FROM locations l 
                WHERE l.is_active = true 
                ORDER BY l.aisle, l.shelf, l.bay";
        return Database::query($sql);
    }

    public static function findByPosition(string $aisle, string $shelf, string $bay): ?array
    {
        $sql = "SELECT * FROM locations WHERE aisle = :aisle AND shelf = :shelf AND bay = :bay LIMIT 1";
        return Database::queryOne($sql, ['aisle' => $aisle, 'shelf' => $shelf, 'bay' => $bay]);
    }

    public static function getFormatted(int $id): string
    {
        $loc = self::find($id);
        if (!$loc) return 'Unknown';
        $parts = [$loc['aisle'], $loc['shelf'], $loc['bay']];
        if (!empty($loc['bin'])) $parts[] = $loc['bin'];
        return implode(' / ', $parts);
    }
}
