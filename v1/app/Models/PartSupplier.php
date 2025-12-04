<?php
/**
 * Part Supplier Model
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class PartSupplier extends Model
{
    protected string $table = 'part_suppliers';

    public function getByPart(int $partId): array
    {
        $sql = "SELECT ps.*, s.name as supplier_name 
                FROM {$this->table} ps
                JOIN suppliers s ON ps.supplier_id = s.id
                WHERE ps.part_id = ?
                ORDER BY s.is_preferred DESC, s.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$partId]);
        return $stmt->fetchAll();
    }

    public function addOrUpdate(int $partId, int $supplierId, array $data): bool
    {
        // Check if exists
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE part_id = ? AND supplier_id = ?");
        $stmt->execute([$partId, $supplierId]);
        $existing = $stmt->fetch();

        if ($existing) {
            return $this->update((int)$existing['id'], $data);
        } else {
            $data['part_id'] = $partId;
            $data['supplier_id'] = $supplierId;
            return (bool)$this->create($data);
        }
    }
}
