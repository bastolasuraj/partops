<?php
/**
 * Location Model
 * 
 * Handle warehouse location data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class Location extends Model
{
    protected string $table = 'locations';

    /**
     * Find existing location by coordinates or create it.
     */
    public function findOrCreate(string $aisle, string $shelf, string $bay, ?string $bin = null): int
    {
        $aisle = trim($aisle);
        $shelf = trim($shelf);
        $bay = trim($bay);
        $bin = $bin !== null ? trim($bin) : null;

        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table} WHERE aisle = ? AND shelf = ? AND bay = ? AND " .
            ($bin === null ? "bin IS NULL" : "bin = ?") .
            " LIMIT 1"
        );
        $stmt->execute($bin === null ? [$aisle, $shelf, $bay] : [$aisle, $shelf, $bay, $bin]);
        $existing = $stmt->fetch();
        if ($existing) {
            return (int)$existing['id'];
        }

        $data = [
            'aisle' => $aisle,
            'shelf' => $shelf,
            'bay' => $bay,
            'bin' => $bin,
            'is_active' => 1
        ];

        $this->create($data);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Format location as string
     */
    public static function formatLocation(array $location): string
    {
        $parts = [$location['aisle'], $location['shelf'], $location['bay']];
        
        if (!empty($location['bin'])) {
            $parts[] = $location['bin'];
        }

        return implode('-', $parts);
    }
}
