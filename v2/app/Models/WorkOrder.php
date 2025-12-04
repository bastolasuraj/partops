<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * WorkOrder Model
 * This file should be moved to: app/Models/WorkOrder.php
 */
class WorkOrder extends Model
{
    protected string $table = 'work_orders';
    protected array $fillable = [
        'wo_number',
        'unit_number',
        'description',
        'status',
        'priority',
        'technician_id',
        'due_date',
        'notes'
    ];

    /**
     * Get all work orders with technician info
     */
    public function getAllWithTechnician(array $filters = []): array
    {
        $sql = "
            SELECT wo.*, t.name AS technician_name,
                   COUNT(DISTINCT im.id) AS parts_count
            FROM work_orders wo
            LEFT JOIN technicians t ON t.id = wo.technician_id
            LEFT JOIN inventory_moves im ON im.work_order_id = wo.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND wo.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['technician_id'])) {
            $sql .= " AND wo.technician_id = :technician_id";
            $params['technician_id'] = $filters['technician_id'];
        }
        
        $sql .= " GROUP BY wo.id ORDER BY wo.opened_at DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Get work order with full details
     */
    public function getWithDetails(int $id): ?array
    {
        $wo = $this->find($id);
        if (!$wo) {
            return null;
        }

        // Get technician
        if ($wo['technician_id']) {
            $techModel = new Technician();
            $wo['technician'] = $techModel->find($wo['technician_id']);
        }

        // Get parts used
        $wo['parts'] = $this->query(
            "SELECT im.*, p.anchor_slug, p.name AS part_name,
                    pn.number AS part_number,
                    l.aisle, l.shelf, l.bay
             FROM inventory_moves im
             JOIN parts p ON p.id = im.part_id
             LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
             JOIN locations l ON l.id = im.location_id
             WHERE im.work_order_id = :id
             ORDER BY im.created_at DESC",
            ['id' => $id]
        );

        return $wo;
    }

    /**
     * Get open work orders
     */
    public function getOpen(): array
    {
        return $this->all(
            ['status' => 'open'],
            'priority DESC, opened_at ASC'
        );
    }

    /**
     * Search work orders
     */
    public function search(string $query): array
    {
        $sql = "
            SELECT wo.*, t.name AS technician_name
            FROM work_orders wo
            LEFT JOIN technicians t ON t.id = wo.technician_id
            WHERE wo.wo_number LIKE :query
               OR wo.unit_number LIKE :query
               OR wo.description LIKE :query
            ORDER BY wo.opened_at DESC
            LIMIT 20
        ";
        
        return $this->query($sql, ['query' => "%{$query}%"]);
    }

    /**
     * Close work order
     */
    public function close(int $id): bool
    {
        return $this->execute(
            "UPDATE work_orders SET status = 'completed', completed_at = NOW(), closed_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Find by work order number
     */
    public function findByNumber(string $woNumber): ?array
    {
        return $this->findBy('wo_number', $woNumber);
    }
}
