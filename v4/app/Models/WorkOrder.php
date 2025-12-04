<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class WorkOrder extends Model
{
    protected string $table = 'work_orders';

    public function find(int $id): ?array
    {
        $sql = "SELECT wo.*, t.name as technician_name 
                FROM {$this->table} wo
                LEFT JOIN technicians t ON wo.technician_id = t.id
                WHERE wo.id = ? LIMIT 1";
        return $this->fetch($sql, [$id]);
    }

    public function findByNumber(string $woNumber): ?array
    {
        // Case-insensitive match to be robust; controller normalizes to uppercase on input.
        $sql = "SELECT * FROM {$this->table} WHERE UPPER(wo_number) = ? LIMIT 1";
        return $this->fetch($sql, [strtoupper($woNumber)]);
    }

    public function getWithParts(int $id): ?array
    {
        $sql = "SELECT
                    pc.work_order_id,
                    pc.part_id,
                    p.fowler_part_number,
                    p.name AS part_name,
                    pc.supplier_part_number,
                    SUM(pc.quantity) AS total_checked_out,
                    (SELECT COALESCE(SUM(return_quantity), 0) 
                     FROM work_order_returns 
                     WHERE work_order_id = pc.work_order_id 
                     AND part_id = pc.part_id) AS total_returned
                FROM part_checkouts pc
                JOIN parts p ON pc.part_id = p.id
                WHERE pc.work_order_id = ?
                GROUP BY pc.work_order_id, pc.part_id, p.fowler_part_number, p.name, pc.supplier_part_number
                ORDER BY p.fowler_part_number, pc.supplier_part_number";
        return $this->fetchAll($sql, [$id]);
    }

    public function getRecent(int $limit = 20): array
    {
        $sql = "SELECT wo.*, t.name as technician_name 
                FROM {$this->table} wo
                LEFT JOIN technicians t ON wo.technician_id = t.id
                ORDER BY wo.created_at DESC
                LIMIT ?";
        return $this->fetchAll($sql, [$limit]);
    }

    public function search(string $query = '', ?string $dateFrom = null, ?string $dateTo = null, int $limit = 50): array
    {
        $sql = "SELECT wo.*, t.name as technician_name 
                FROM {$this->table} wo
                LEFT JOIN technicians t ON wo.technician_id = t.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (wo.wo_number LIKE ? OR wo.unit_number LIKE ? OR t.name LIKE ?)";
            $searchTerm = "%{$query}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($dateFrom)) {
            $sql .= " AND wo.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $sql .= " AND wo.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        $sql .= " ORDER BY wo.created_at DESC LIMIT " . (int)$limit;

        return $this->fetchAll($sql, $params);
    }
}
