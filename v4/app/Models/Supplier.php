<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Supplier extends Model
{
    protected string $table = 'suppliers';

    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name";
        return $this->fetchAll($sql);
    }

    public function getSuppliedParts(): array
    {
        $sql = "SELECT psn.supplier_id,
                       p.id AS part_id,
                       p.fowler_part_number,
                       p.name AS part_name,
                       psn.supplier_part_number
                FROM part_supplier_numbers psn
                JOIN parts p ON psn.part_id = p.id
                ORDER BY psn.supplier_id, p.fowler_part_number";
        $rows = $this->fetchAll($sql);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['supplier_id']][] = [
                'part_id' => $row['part_id'],
                'fowler_part_number' => $row['fowler_part_number'],
                'part_name' => $row['part_name'],
                'supplier_part_number' => $row['supplier_part_number'],
            ];
        }
        return $grouped;
    }
}
