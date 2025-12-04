<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * InventoryLevel Model
 * This file should be moved to: app/Models/InventoryLevel.php
 */
class InventoryLevel extends Model
{
    protected string $table = 'inventory_levels';
    protected array $fillable = [
        'part_id',
        'location_id',
        'on_hand',
        'reserved',
        'last_count_date',
        'last_count_qty'
    ];
    protected bool $timestamps = false;

    /**
     * Get or create inventory level for part at location
     */
    public function getOrCreate(int $partId, int $locationId): array
    {
        $existing = $this->query(
            "SELECT * FROM inventory_levels WHERE part_id = :part_id AND location_id = :location_id",
            ['part_id' => $partId, 'location_id' => $locationId]
        );

        if (!empty($existing)) {
            return $existing[0];
        }

        $id = $this->create([
            'part_id' => $partId,
            'location_id' => $locationId,
            'on_hand' => 0,
            'reserved' => 0
        ]);

        return $this->find($id);
    }

    /**
     * Adjust stock level with row locking
     */
    public function adjustStock(int $partId, int $locationId, int $qty): bool
    {
        $this->beginTransaction();
        
        try {
            // Lock the row for update
            $sql = "SELECT * FROM inventory_levels 
                    WHERE part_id = :part_id AND location_id = :location_id 
                    FOR UPDATE";
            $result = $this->query($sql, ['part_id' => $partId, 'location_id' => $locationId]);
            
            if (empty($result)) {
                // Create new record
                $this->create([
                    'part_id' => $partId,
                    'location_id' => $locationId,
                    'on_hand' => max(0, $qty),
                    'reserved' => 0
                ]);
            } else {
                $current = $result[0];
                $newQty = $current['on_hand'] + $qty;
                
                // Prevent negative stock unless explicitly allowed
                if ($newQty < 0) {
                    $this->rollback();
                    return false;
                }
                
                $this->execute(
                    "UPDATE inventory_levels SET on_hand = :qty, updated_at = NOW() 
                     WHERE part_id = :part_id AND location_id = :location_id",
                    ['qty' => $newQty, 'part_id' => $partId, 'location_id' => $locationId]
                );
            }
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Reserve stock for work order
     */
    public function reserveStock(int $partId, int $locationId, int $qty): bool
    {
        $this->beginTransaction();
        
        try {
            $sql = "SELECT * FROM inventory_levels 
                    WHERE part_id = :part_id AND location_id = :location_id 
                    FOR UPDATE";
            $result = $this->query($sql, ['part_id' => $partId, 'location_id' => $locationId]);
            
            if (empty($result)) {
                $this->rollback();
                return false;
            }
            
            $current = $result[0];
            $available = $current['on_hand'] - $current['reserved'];
            
            if ($qty > $available) {
                $this->rollback();
                return false;
            }
            
            $this->execute(
                "UPDATE inventory_levels SET reserved = reserved + :qty, updated_at = NOW() 
                 WHERE part_id = :part_id AND location_id = :location_id",
                ['qty' => $qty, 'part_id' => $partId, 'location_id' => $locationId]
            );
            
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Release reserved stock
     */
    public function releaseReservation(int $partId, int $locationId, int $qty): bool
    {
        return $this->execute(
            "UPDATE inventory_levels SET reserved = GREATEST(0, reserved - :qty), updated_at = NOW() 
             WHERE part_id = :part_id AND location_id = :location_id",
            ['qty' => $qty, 'part_id' => $partId, 'location_id' => $locationId]
        );
    }

    /**
     * Get total stock for a part across all locations
     */
    public function getTotalStock(int $partId): array
    {
        $sql = "SELECT COALESCE(SUM(on_hand), 0) AS total_on_hand,
                       COALESCE(SUM(reserved), 0) AS total_reserved,
                       COALESCE(SUM(on_hand - reserved), 0) AS total_available
                FROM inventory_levels WHERE part_id = :part_id";
        
        $result = $this->query($sql, ['part_id' => $partId]);
        return $result[0] ?? ['total_on_hand' => 0, 'total_reserved' => 0, 'total_available' => 0];
    }

    /**
     * Get stock by location for a part
     */
    public function getStockByLocation(int $partId): array
    {
        $sql = "SELECT il.*, l.aisle, l.shelf, l.bay, l.bin
                FROM inventory_levels il
                JOIN locations l ON l.id = il.location_id
                WHERE il.part_id = :part_id
                ORDER BY l.aisle, l.shelf, l.bay";
        
        return $this->query($sql, ['part_id' => $partId]);
    }
}
