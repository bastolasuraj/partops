<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class PartNumber extends BaseModel
{
    protected static string $table = 'part_numbers';

    public static function getByPart(int $partId): array
    {
        $sql = "SELECT * FROM part_numbers WHERE part_id = :part_id ORDER BY is_primary DESC, type, value";
        return Database::query($sql, ['part_id' => $partId]);
    }

    public static function findByValue(string $value): ?array
    {
        $sql = "SELECT pn.*, p.anchor_slug, p.name as part_name 
                FROM part_numbers pn 
                JOIN parts p ON pn.part_id = p.id 
                WHERE pn.value = :value LIMIT 1";
        return Database::queryOne($sql, ['value' => $value]);
    }

    public static function setPrimary(int $partId, int $partNumberId): bool
    {
        Database::beginTransaction();
        try {
            Database::execute("UPDATE part_numbers SET is_primary = false WHERE part_id = :part_id", ['part_id' => $partId]);
            Database::execute("UPDATE part_numbers SET is_primary = true WHERE id = :id", ['id' => $partNumberId]);
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            return false;
        }
    }
}
