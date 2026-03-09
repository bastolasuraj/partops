<?php
namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class WorkOrder extends BaseModel
{
    protected string $table = 'work_orders';
    protected array $fillable = ['wo_number'];
    
    public function getAllWithDetails(): array
    {
        $sql = "SELECT 
                    wo.*,
                    COALESCE(it.item_count, 0) as item_count
                FROM 
                    work_orders wo
                LEFT JOIN (
                    SELECT 
                        reference_id, 
                        COUNT(id) as item_count 
                    FROM 
                        inventory_transactions 
                    WHERE 
                        reference_type = 'work_order' AND transaction_type = 'outgoing'
                    GROUP BY 
                        reference_id
                ) it ON wo.id = it.reference_id
                ORDER BY wo.created_at DESC";
        return Database::query($sql)->fetchAll();
    }
    
    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT 
                    wo.*,
                    COALESCE(it.item_count, 0) as item_count
                FROM 
                    work_orders wo
                LEFT JOIN (
                    SELECT 
                        reference_id, 
                        COUNT(id) as item_count 
                    FROM 
                        inventory_transactions 
                    WHERE 
                        reference_type = 'work_order' AND transaction_type = 'outgoing'
                    GROUP BY 
                        reference_id
                ) it ON wo.id = it.reference_id
                WHERE wo.id = ?";
        $result = Database::query($sql, [$id])->fetch();
        return $result ?: null;
    }
    
    public function generateWoNumber(): string
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(wo_number, 4) AS UNSIGNED)) as max_num FROM work_orders WHERE wo_number LIKE 'WO-%'";
        $result = Database::query($sql)->fetch();
        $nextNum = ($result['max_num'] ?? 5000) + 1;
        return 'WO-' . $nextNum;
    }
}
