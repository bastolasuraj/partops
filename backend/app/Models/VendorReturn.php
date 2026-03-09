<?php
namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class VendorReturn extends BaseModel
{
    protected string $table = 'vendor_returns';
    protected array $fillable = [
        'supplier_id', 'part_id', 'quantity', 'rma_number',
        'core_cost', 'core_rebate', 'status', 'notes'
    ];
    
    public function getAllWithDetails(): array
    {
        $sql = "SELECT vr.*, 
                       s.name as supplier_name,
                       p.fowler_part_number, p.name as part_name
                FROM vendor_returns vr
                JOIN suppliers s ON vr.supplier_id = s.id
                JOIN parts p ON vr.part_id = p.id
                ORDER BY vr.created_at DESC";
        return Database::query($sql)->fetchAll();
    }
    
    public function getPending(): array
    {
        return $this->where('status', 'pending');
    }
}
