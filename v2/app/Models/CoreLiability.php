<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * CoreLiability Model - Track core charges and rebates
 * This file should be moved to: app/Models/CoreLiability.php
 */
class CoreLiability extends Model
{
    protected string $table = 'core_liabilities';
    protected array $fillable = [
        'receive_move_id',
        'part_id',
        'part_number_id',
        'supplier_id',
        'qty_due',
        'qty_returned',
        'core_charge',
        'expected_rebate',
        'actual_rebate',
        'due_date',
        'status',
        'sent_at',
        'rebate_received_at',
        'rma_number',
        'tracking_number',
        'notes'
    ];

    /**
     * Get all pending core liabilities
     */
    public function getPending(): array
    {
        $sql = "
            SELECT cl.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   s.name AS supplier_name,
                   DATEDIFF(cl.due_date, CURDATE()) AS days_until_due
            FROM core_liabilities cl
            JOIN parts p ON p.id = cl.part_id
            LEFT JOIN part_numbers pn ON pn.id = cl.part_number_id
            JOIN suppliers s ON s.id = cl.supplier_id
            WHERE cl.status IN ('pending', 'partial')
            ORDER BY cl.due_date ASC
        ";
        
        return $this->query($sql);
    }

    /**
     * Get overdue core liabilities
     */
    public function getOverdue(): array
    {
        $sql = "
            SELECT cl.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number,
                   s.name AS supplier_name,
                   DATEDIFF(CURDATE(), cl.due_date) AS days_overdue
            FROM core_liabilities cl
            JOIN parts p ON p.id = cl.part_id
            LEFT JOIN part_numbers pn ON pn.id = cl.part_number_id
            JOIN suppliers s ON s.id = cl.supplier_id
            WHERE cl.status IN ('pending', 'partial')
              AND cl.due_date < CURDATE()
            ORDER BY cl.due_date ASC
        ";
        
        return $this->query($sql);
    }

    /**
     * Get total pending core liability value
     */
    public function getTotalPendingValue(): array
    {
        $sql = "
            SELECT 
                SUM((cl.qty_due - cl.qty_returned) * cl.core_charge) AS total_core_charge,
                SUM((cl.qty_due - cl.qty_returned) * cl.expected_rebate) AS total_expected_rebate
            FROM core_liabilities cl
            WHERE cl.status IN ('pending', 'partial')
        ";
        
        $result = $this->query($sql);
        return $result[0] ?? ['total_core_charge' => 0, 'total_expected_rebate' => 0];
    }

    /**
     * Mark core as sent
     */
    public function markSent(int $id, array $data = []): bool
    {
        $updateData = [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($data['rma_number'])) {
            $updateData['rma_number'] = $data['rma_number'];
        }
        if (!empty($data['tracking_number'])) {
            $updateData['tracking_number'] = $data['tracking_number'];
        }
        if (!empty($data['notes'])) {
            $updateData['notes'] = $data['notes'];
        }
        
        return $this->update($id, $updateData);
    }

    /**
     * Record rebate received
     */
    public function recordRebate(int $id, float $amount, ?string $notes = null): bool
    {
        $updateData = [
            'status' => 'rebated',
            'actual_rebate' => $amount,
            'rebate_received_at' => date('Y-m-d H:i:s')
        ];
        
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        return $this->update($id, $updateData);
    }

    /**
     * Get core liabilities by supplier
     */
    public function getBySupplier(int $supplierId): array
    {
        $sql = "
            SELECT cl.*, 
                   p.anchor_slug, p.name AS part_name,
                   pn.number AS part_number
            FROM core_liabilities cl
            JOIN parts p ON p.id = cl.part_id
            LEFT JOIN part_numbers pn ON pn.id = cl.part_number_id
            WHERE cl.supplier_id = :supplier_id
            ORDER BY cl.status, cl.due_date
        ";
        
        return $this->query($sql, ['supplier_id' => $supplierId]);
    }

    /**
     * Get summary by status
     */
    public function getSummaryByStatus(): array
    {
        $sql = "
            SELECT 
                status,
                COUNT(*) AS count,
                SUM(qty_due) AS total_qty,
                SUM(core_charge * qty_due) AS total_charge,
                SUM(expected_rebate * qty_due) AS total_expected
            FROM core_liabilities
            GROUP BY status
        ";
        
        return $this->query($sql);
    }
}
