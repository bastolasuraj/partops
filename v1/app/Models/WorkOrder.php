<?php
/**
 * Work Order Model
 * 
 * Handle work order data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class WorkOrder extends Model
{
    protected string $table = 'work_orders';

    /**
     * Find work order by external reference
     */
    public function findByExternalRef(string $ref): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE external_ref = ? LIMIT 1"
        );
        $stmt->execute([$ref]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get open work orders
     */
    public function getOpen(): array
    {
        return $this->all(['status' => 'open']);
    }
}
