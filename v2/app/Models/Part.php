<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Part Model
 * This file should be moved to: app/Models/Part.php
 */
class Part extends Model
{
    protected string $table = 'parts';
    protected array $fillable = [
        'anchor_slug',
        'name',
        'description',
        'category',
        'notes',
        'min_stock_level',
        'is_active'
    ];

    /**
     * Search parts by any part number, anchor, or name
     */
    public function search(string $query, int $limit = 20): array
    {
        $sql = "
            SELECT DISTINCT p.*, 
                   pn.number AS primary_number,
                   pn.manufacturer,
                   COALESCE(SUM(il.on_hand), 0) AS total_stock
            FROM parts p
            LEFT JOIN part_numbers pn ON pn.part_id = p.id AND pn.is_primary = 1
            LEFT JOIN inventory_levels il ON il.part_id = p.id
            LEFT JOIN part_numbers pn_search ON pn_search.part_id = p.id
            WHERE p.is_active = 1
              AND (
                  p.anchor_slug LIKE :query
                  OR p.name LIKE :query
                  OR pn_search.number LIKE :query
                  OR pn_search.manufacturer LIKE :query
                  OR MATCH(p.name, p.description, p.anchor_slug) AGAINST(:fulltext IN BOOLEAN MODE)
              )
            GROUP BY p.id
            ORDER BY p.name
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $searchTerm = "%{$query}%";
        $fulltextTerm = $query . '*';
        $stmt->bindValue(':query', $searchTerm);
        $stmt->bindValue(':fulltext', $fulltextTerm);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get part with all related data
     */
    public function getWithDetails(int $id): ?array
    {
        $part = $this->find($id);
        if (!$part) {
            return null;
        }

        // Get part numbers
        $part['part_numbers'] = $this->query(
            "SELECT * FROM part_numbers WHERE part_id = :id ORDER BY is_primary DESC, type",
            ['id' => $id]
        );

        // Get suppliers with pricing
        $part['suppliers'] = $this->query(
            "SELECT ps.*, s.name AS supplier_name, s.is_preferred AS supplier_preferred
             FROM part_suppliers ps
             JOIN suppliers s ON s.id = ps.supplier_id
             WHERE ps.part_id = :id
             ORDER BY ps.is_preferred DESC, s.name",
            ['id' => $id]
        );

        // Get inventory levels
        $part['inventory'] = $this->query(
            "SELECT il.*, l.aisle, l.shelf, l.bay, l.bin
             FROM inventory_levels il
             JOIN locations l ON l.id = il.location_id
             WHERE il.part_id = :id",
            ['id' => $id]
        );

        // Calculate totals
        $part['total_on_hand'] = array_sum(array_column($part['inventory'], 'on_hand'));
        $part['total_reserved'] = array_sum(array_column($part['inventory'], 'reserved'));
        $part['total_available'] = $part['total_on_hand'] - $part['total_reserved'];

        return $part;
    }

    /**
     * Get parts with low stock
     */
    public function getLowStock(): array
    {
        $sql = "
            SELECT p.*, 
                   pn.number AS primary_number,
                   COALESCE(SUM(il.on_hand), 0) AS total_stock
            FROM parts p
            LEFT JOIN part_numbers pn ON pn.part_id = p.id AND pn.is_primary = 1
            LEFT JOIN inventory_levels il ON il.part_id = p.id
            WHERE p.is_active = 1
            GROUP BY p.id, pn.id, pn.number
            HAVING total_stock <= p.min_stock_level
            ORDER BY total_stock ASC
        ";
        
        return $this->query($sql);
    }

    /**
     * Get all parts with stock summary
     */
    public function getAllWithStock(array $filters = []): array
    {
        $sql = "
            SELECT p.*, 
                   pn.number AS primary_number,
                   pn.manufacturer,
                   COALESCE(SUM(il.on_hand), 0) AS total_on_hand,
                   COALESCE(SUM(il.reserved), 0) AS total_reserved
            FROM parts p
            LEFT JOIN part_numbers pn ON pn.part_id = p.id AND pn.is_primary = 1
            LEFT JOIN inventory_levels il ON il.part_id = p.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (isset($filters['is_active'])) {
            $sql .= " AND p.is_active = :is_active";
            $params['is_active'] = $filters['is_active'];
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category = :category";
            $params['category'] = $filters['category'];
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.name";
        
        return $this->query($sql, $params);
    }
}
