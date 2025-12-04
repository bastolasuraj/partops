<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PartSupplierNumber extends Model
{
    protected string $table = 'part_supplier_numbers';

    public function getByPart(int $partId): array
    {
        $sql = "SELECT psn.*, s.name as supplier_name
                FROM {$this->table} psn
                JOIN suppliers s ON psn.supplier_id = s.id
                WHERE psn.part_id = ?
                ORDER BY s.name, psn.supplier_part_number";
        return $this->fetchAll($sql, [$partId]);
    }

    public function syncForPart(int $partId, array $entries): void
    {
        Database::query("DELETE FROM {$this->table} WHERE part_id = ?", [$partId]);

        if (empty($entries)) {
            return;
        }

        foreach ($entries as $row) {
            Database::query(
                "INSERT INTO {$this->table} (part_id, supplier_id, supplier_part_number) VALUES (?, ?, ?)",
                [$partId, (int)$row['supplier_id'], $row['supplier_part_number']]
            );
        }
    }
}
