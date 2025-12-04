<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * PartNumber Model
 * This file should be moved to: app/Models/PartNumber.php
 */
class PartNumber extends Model
{
    protected string $table = 'part_numbers';
    protected array $fillable = [
        'part_id',
        'number',
        'type',
        'manufacturer',
        'is_primary',
        'is_active',
        'notes'
    ];

    /**
     * Get all part numbers for a part
     */
    public function getByPartId(int $partId): array
    {
        return $this->all(['part_id' => $partId], 'is_primary DESC, type, number');
    }

    /**
     * Find part by any part number
     */
    public function findPartByNumber(string $number): ?array
    {
        $sql = "
            SELECT p.*, pn.number AS matched_number, pn.type AS matched_type
            FROM part_numbers pn
            JOIN parts p ON p.id = pn.part_id
            WHERE pn.number = :number AND pn.is_active = 1 AND p.is_active = 1
            LIMIT 1
        ";
        
        $result = $this->query($sql, ['number' => $number]);
        return $result[0] ?? null;
    }

    /**
     * Set a part number as primary (and unset others)
     */
    public function setPrimary(int $partNumberId, int $partId): bool
    {
        $this->beginTransaction();
        try {
            // Unset all primary flags for this part
            $this->execute(
                "UPDATE part_numbers SET is_primary = 0 WHERE part_id = :part_id",
                ['part_id' => $partId]
            );
            
            // Set the new primary
            $this->execute(
                "UPDATE part_numbers SET is_primary = 1 WHERE id = :id",
                ['id' => $partNumberId]
            );
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * Search part numbers
     */
    public function search(string $query): array
    {
        $sql = "
            SELECT pn.*, p.anchor_slug, p.name AS part_name
            FROM part_numbers pn
            JOIN parts p ON p.id = pn.part_id
            WHERE pn.is_active = 1 AND p.is_active = 1
              AND (pn.number LIKE :query OR pn.manufacturer LIKE :query)
            ORDER BY pn.number
            LIMIT 50
        ";
        
        return $this->query($sql, ['query' => "%{$query}%"]);
    }
}
