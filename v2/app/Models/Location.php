<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Location Model
 * This file should be moved to: app/Models/Location.php
 */
class Location extends Model
{
    protected string $table = 'locations';
    protected array $fillable = [
        'aisle',
        'shelf',
        'bay',
        'bin',
        'description',
        'is_active'
    ];

    /**
     * Get all active locations
     */
    public function getActive(): array
    {
        return $this->all(['is_active' => 1], 'aisle, shelf, bay, bin');
    }

    /**
     * Get location display string
     */
    public function getDisplayString(array $location): string
    {
        $parts = [$location['aisle'], $location['shelf'], $location['bay']];
        if (!empty($location['bin'])) {
            $parts[] = $location['bin'];
        }
        return implode(' / ', $parts);
    }

    /**
     * Find location by components
     */
    public function findByComponents(string $aisle, string $shelf, string $bay, ?string $bin = null): ?array
    {
        $conditions = [
            'aisle' => $aisle,
            'shelf' => $shelf,
            'bay' => $bay
        ];
        
        if ($bin !== null) {
            $conditions['bin'] = $bin;
        }
        
        $sql = "SELECT * FROM locations WHERE aisle = :aisle AND shelf = :shelf AND bay = :bay";
        $params = ['aisle' => $aisle, 'shelf' => $shelf, 'bay' => $bay];
        
        if ($bin !== null) {
            $sql .= " AND bin = :bin";
            $params['bin'] = $bin;
        } else {
            $sql .= " AND bin IS NULL";
        }
        
        $sql .= " LIMIT 1";
        
        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    /**
     * Get locations with inventory count
     */
    public function getWithInventoryCount(): array
    {
        $sql = "
            SELECT l.*, 
                   COUNT(DISTINCT il.part_id) AS parts_count,
                   COALESCE(SUM(il.on_hand), 0) AS total_items
            FROM locations l
            LEFT JOIN inventory_levels il ON il.location_id = l.id
            WHERE l.is_active = 1
            GROUP BY l.id
            ORDER BY l.aisle, l.shelf, l.bay, l.bin
        ";
        
        return $this->query($sql);
    }

    /**
     * Get all aisles
     */
    public function getAisles(): array
    {
        $sql = "SELECT DISTINCT aisle FROM locations WHERE is_active = 1 ORDER BY aisle";
        return array_column($this->query($sql), 'aisle');
    }

    /**
     * Get parts at location
     */
    public function getPartsAtLocation(int $locationId): array
    {
        $sql = "
            SELECT p.*, il.on_hand, il.reserved, il.available,
                   pn.number AS primary_number
            FROM inventory_levels il
            JOIN parts p ON p.id = il.part_id
            LEFT JOIN part_numbers pn ON pn.part_id = p.id AND pn.is_primary = 1
            WHERE il.location_id = :location_id AND p.is_active = 1
            ORDER BY p.name
        ";
        
        return $this->query($sql, ['location_id' => $locationId]);
    }
}
