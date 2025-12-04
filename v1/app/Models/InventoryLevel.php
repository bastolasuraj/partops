<?php
/**
 * Inventory Level Model
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class InventoryLevel extends Model
{
    protected string $table = 'inventory_levels';

    public function getByPartAndLocation(int $partId, int $locationId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE part_id = ? AND location_id = ? LIMIT 1"
        );
        $stmt->execute([$partId, $locationId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getByPart(int $partId): array
    {
        $stmt = $this->db->prepare(
            "SELECT il.*, l.aisle, l.shelf, l.bay, l.bin 
             FROM {$this->table} il
             JOIN locations l ON il.location_id = l.id
             WHERE il.part_id = ?"
        );
        $stmt->execute([$partId]);
        return $stmt->fetchAll();
    }

    /**
     * Increment or decrement stock
     * Creates row if not exists (for increment)
     */
    public function adjustStock(int $partId, int $locationId, int $qtyDelta): void
    {
        // Check if exists
        $current = $this->getByPartAndLocation($partId, $locationId);

        if ($current) {
            $newQty = $current['on_hand'] + $qtyDelta;
            if ($newQty < 0) {
                throw new \Exception("Insufficient stock");
            }
            
            $stmt = $this->db->prepare("UPDATE {$this->table} SET on_hand = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newQty, $current['id']]);
        } else {
            if ($qtyDelta < 0) {
                throw new \Exception("Cannot decrement stock: location not found for part");
            }
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (part_id, location_id, on_hand) VALUES (?, ?, ?)");
            $stmt->execute([$partId, $locationId, $qtyDelta]);
        }
    }
}
