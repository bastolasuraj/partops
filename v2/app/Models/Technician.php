<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Technician Model
 * This file should be moved to: app/Models/Technician.php
 */
class Technician extends Model
{
    protected string $table = 'technicians';
    protected array $fillable = [
        'employee_id',
        'name',
        'email',
        'phone',
        'department',
        'is_active'
    ];

    /**
     * Get all active technicians
     */
    public function getActive(): array
    {
        return $this->all(['is_active' => 1], 'name');
    }

    /**
     * Get technician with work order stats
     */
    public function getWithStats(): array
    {
        $sql = "
            SELECT t.*,
                   COUNT(DISTINCT wo.id) AS total_work_orders,
                   COUNT(DISTINCT CASE WHEN wo.status IN ('open', 'in_progress') THEN wo.id END) AS active_work_orders,
                   COALESCE(SUM(ABS(im.qty)), 0) AS total_parts_used
            FROM technicians t
            LEFT JOIN work_orders wo ON wo.technician_id = t.id
            LEFT JOIN inventory_moves im ON im.technician_id = t.id AND im.reason = 'checkout'
            WHERE t.is_active = 1
            GROUP BY t.id
            ORDER BY t.name
        ";
        
        return $this->query($sql);
    }

    /**
     * Get active checkouts for technician
     */
    public function getActiveCheckouts(int $technicianId): array
    {
        $sql = "
            SELECT im.*, p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   wo.wo_number, wo.unit_number,
                   l.aisle, l.shelf, l.bay
            FROM inventory_moves im
            JOIN parts p ON p.id = im.part_id
            LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
            LEFT JOIN work_orders wo ON wo.id = im.work_order_id
            JOIN locations l ON l.id = im.location_id
            WHERE im.technician_id = :technician_id
              AND im.reason = 'checkout'
              AND im.direction = 'out'
            ORDER BY im.created_at DESC
        ";
        
        return $this->query($sql, ['technician_id' => $technicianId]);
    }

    /**
     * Search technicians
     */
    public function search(string $query): array
    {
        $sql = "
            SELECT * FROM technicians
            WHERE is_active = 1
              AND (name LIKE :query OR employee_id LIKE :query OR email LIKE :query)
            ORDER BY name
            LIMIT 20
        ";
        
        return $this->query($sql, ['query' => "%{$query}%"]);
    }
}
