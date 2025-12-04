<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class Supplier extends BaseModel
{
    protected static string $table = 'suppliers';

    public static function getActive(): array
    {
        $sql = "SELECT s.*, 
                    (SELECT COUNT(*) FROM part_suppliers ps WHERE ps.supplier_id = s.id) as parts_count
                FROM suppliers s 
                WHERE s.is_active = true 
                ORDER BY s.is_preferred DESC, s.name";
        return Database::query($sql);
    }

    public static function getPreferred(): array
    {
        $sql = "SELECT * FROM suppliers WHERE is_active = true AND is_preferred = true ORDER BY name";
        return Database::query($sql);
    }

    public static function search(string $query): array
    {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT * FROM suppliers 
                WHERE (name LIKE :q1 OR contact_email LIKE :q2) AND is_active = true 
                ORDER BY name";
        return Database::query($sql, ['q1' => $searchTerm, 'q2' => $searchTerm]);
    }
}
