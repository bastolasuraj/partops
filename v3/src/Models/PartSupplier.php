<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class PartSupplier extends BaseModel
{
    protected static string $table = 'part_suppliers';

    public static function getByPart(int $partId): array
    {
        $sql = "SELECT ps.*, s.name as supplier_name, s.reorder_url, s.is_preferred 
                FROM part_suppliers ps 
                JOIN suppliers s ON ps.supplier_id = s.id 
                WHERE ps.part_id = :part_id 
                ORDER BY s.is_preferred DESC, s.name";
        return Database::query($sql, ['part_id' => $partId]);
    }

    public static function getBySupplier(int $supplierId): array
    {
        $sql = "SELECT ps.*, p.anchor_slug, p.name as part_name 
                FROM part_suppliers ps 
                JOIN parts p ON ps.part_id = p.id 
                WHERE ps.supplier_id = :supplier_id 
                ORDER BY p.name";
        return Database::query($sql, ['supplier_id' => $supplierId]);
    }

    public static function findByPartAndSupplier(int $partId, int $supplierId): ?array
    {
        $sql = "SELECT * FROM part_suppliers WHERE part_id = :part_id AND supplier_id = :supplier_id LIMIT 1";
        return Database::queryOne($sql, ['part_id' => $partId, 'supplier_id' => $supplierId]);
    }
}
