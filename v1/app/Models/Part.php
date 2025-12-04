<?php
/**
 * Part Model
 * 
 * Handle part data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class Part extends Model
{
    protected string $table = 'parts';

    /**
     * Find part by anchor slug
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE anchor_slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Search parts by fulltext or part number
     */
    public function search(string $query): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.*, 
                    (MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)) as relevance
             FROM {$this->table} p
             LEFT JOIN part_numbers pn ON p.id = pn.part_id
             WHERE MATCH(p.name, p.description) AGAINST(? IN NATURAL LANGUAGE MODE)
                OR p.anchor_slug LIKE ?
                OR pn.value LIKE ?
                OR MATCH(pn.value, pn.manufacturer) AGAINST(? IN NATURAL LANGUAGE MODE)
             ORDER BY relevance DESC, p.name ASC
             LIMIT 50"
        );
        
        $likeQuery = "%{$query}%";
        // Params: 1. Relevance, 2. Match Name/Desc, 3. Slug Like, 4. PN Value Like, 5. Match PN/Mfr
        $stmt->execute([$query, $query, $likeQuery, $likeQuery, $query]);
        
        return $stmt->fetchAll();
    }

    /**
     * Add a part number
     */
    public function addNumber(int $partId, array $data): bool
    {
        $sql = "INSERT INTO part_numbers (part_id, value, type, manufacturer, is_primary) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $partId,
            $data['value'],
            $data['type'] ?? 'active',
            $data['manufacturer'] ?? null,
            !empty($data['is_primary']) ? 1 : 0
        ]);
    }

    /**
     * Get all parts with stock info
     */
    public function getAllWithStock(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT p.*, 
                       (SELECT value FROM part_numbers WHERE part_id = p.id AND is_primary = 1 LIMIT 1) as primary_number,
                       COALESCE(SUM(il.on_hand), 0) as total_stock,
                       GROUP_CONCAT(DISTINCT CONCAT(l.aisle, '-', l.shelf, '-', l.bay) SEPARATOR ', ') as locations
                FROM {$this->table} p
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                LEFT JOIN locations l ON il.location_id = l.id
                WHERE p.is_active = 1
                GROUP BY p.id
                ORDER BY p.name ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get full part details
     */
    public function getDetails(int $id): ?array
    {
        $part = $this->find($id);
        if (!$part) return null;

        // Numbers
        $stmt = $this->db->prepare("SELECT * FROM part_numbers WHERE part_id = ? ORDER BY is_primary DESC, type ASC");
        $stmt->execute([$id]);
        $part['numbers'] = $stmt->fetchAll();

        // Suppliers
        $stmt = $this->db->prepare(
            "SELECT ps.*, s.name as supplier_name, s.is_preferred 
             FROM part_suppliers ps 
             JOIN suppliers s ON ps.supplier_id = s.id 
             WHERE ps.part_id = ?
             ORDER BY s.is_preferred DESC"
        );
        $stmt->execute([$id]);
        $part['suppliers'] = $stmt->fetchAll();

        // Inventory
        $stmt = $this->db->prepare(
            "SELECT il.*, l.aisle, l.shelf, l.bay, l.bin 
             FROM inventory_levels il 
             JOIN locations l ON il.location_id = l.id 
             WHERE il.part_id = ?"
        );
        $stmt->execute([$id]);
        $part['inventory'] = $stmt->fetchAll();

        return $part;
    }
}
