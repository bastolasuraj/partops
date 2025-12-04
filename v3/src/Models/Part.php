<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class Part extends BaseModel
{
    protected static string $table = 'parts';

    public static function findByAnchor(string $anchor): ?array
    {
        $sql = "SELECT * FROM parts WHERE anchor_slug = :anchor LIMIT 1";
        return Database::queryOne($sql, ['anchor' => $anchor]);
    }

    public static function search(string $query, bool $activeOnly = true, int $limit = 50): array
    {
        $searchTerm = '%' . $query . '%';
        
        $sql = "SELECT DISTINCT p.*, 
                    (SELECT pn.value FROM part_numbers pn WHERE pn.part_id = p.id AND pn.is_primary = true LIMIT 1) as active_number,
                    (SELECT GROUP_CONCAT(pn2.value SEPARATOR ', ') FROM part_numbers pn2 WHERE pn2.part_id = p.id AND pn2.is_primary = false) as alternates,
                    (SELECT SUM(il.on_hand) FROM inventory_levels il WHERE il.part_id = p.id) as total_stock
                FROM parts p
                LEFT JOIN part_numbers pn ON p.id = pn.part_id
                WHERE (p.anchor_slug LIKE :q1 OR p.name LIKE :q2 OR p.description LIKE :q3 
                       OR pn.value LIKE :q4 OR pn.manufacturer LIKE :q5)";
        
        if ($activeOnly) {
            $sql .= " AND p.is_active = true";
        }
        
        $sql .= " ORDER BY p.name LIMIT :limit";

        return Database::query($sql, [
            'q1' => $searchTerm, 'q2' => $searchTerm, 'q3' => $searchTerm,
            'q4' => $searchTerm, 'q5' => $searchTerm, 'limit' => $limit
        ]);
    }

    public static function getWithDetails(int $id): ?array
    {
        $part = self::find($id);
        if (!$part) return null;

        $part['part_numbers'] = PartNumber::getByPart($id);
        $part['suppliers'] = PartSupplier::getByPart($id);
        $part['inventory'] = InventoryLevel::getByPart($id);
        
        return $part;
    }

    public static function getAllWithSummary(bool $activeOnly = true, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT p.*, 
                    (SELECT pn.value FROM part_numbers pn WHERE pn.part_id = p.id AND pn.is_primary = true LIMIT 1) as active_number,
                    (SELECT GROUP_CONCAT(pn2.value SEPARATOR ', ') FROM part_numbers pn2 WHERE pn2.part_id = p.id AND pn2.is_primary = false LIMIT 3) as alternates,
                    (SELECT s.name FROM part_suppliers ps JOIN suppliers s ON ps.supplier_id = s.id WHERE ps.part_id = p.id LIMIT 1) as supplier_name,
                    (SELECT SUM(il.on_hand) FROM inventory_levels il WHERE il.part_id = p.id) as total_stock,
                    (SELECT CONCAT(l.aisle, '/', l.shelf, '/', l.bay) FROM inventory_levels il2 JOIN locations l ON il2.location_id = l.id WHERE il2.part_id = p.id LIMIT 1) as location,
                    (SELECT ps2.core_charge FROM part_suppliers ps2 WHERE ps2.part_id = p.id LIMIT 1) as core_charge,
                    (SELECT ps3.expected_rebate FROM part_suppliers ps3 WHERE ps3.part_id = p.id LIMIT 1) as expected_rebate
                FROM parts p";
        
        if ($activeOnly) {
            $sql .= " WHERE p.is_active = true";
        }
        
        $sql .= " ORDER BY p.name LIMIT :limit OFFSET :offset";

        return Database::query($sql, ['limit' => $limit, 'offset' => $offset]);
    }
}
