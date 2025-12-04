<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Part extends Model
{
    protected string $table = 'parts';

    public function getWithInventory(): array
    {
        $sql = "SELECT * FROM v_inventory_levels ORDER BY fowler_part_number";
        return $this->fetchAll($sql);
    }

    public function findByPartNumber(string $partNumber): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE fowler_part_number = ? LIMIT 1";
        return $this->fetch($sql, [$partNumber]);
    }

    public function getInventoryLevel(int $partId): ?array
    {
        $sql = "SELECT * FROM v_inventory_levels WHERE part_id = ? LIMIT 1";
        return $this->fetch($sql, [$partId]);
    }

    public function search(string $query): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE fowler_part_number LIKE ? 
                OR name LIKE ? 
                OR supplier_part_number LIKE ?
                ORDER BY fowler_part_number
                LIMIT 50";
        $searchTerm = "%{$query}%";
        return $this->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }

    public function getLowStock(): array
    {
        $sql = "SELECT * FROM v_inventory_levels WHERE is_low_stock = 1";
        return $this->fetchAll($sql);
    }

    public function getDetailed(int $id): ?array
    {
        $sql = "SELECT p.*, 
                       CONCAT_WS(' ', p.location_aisle, p.location_shelf, p.location_bay) AS location
                FROM {$this->table} p
                WHERE p.id = ?
                LIMIT 1";
        return $this->fetch($sql, [$id]);
    }
}
