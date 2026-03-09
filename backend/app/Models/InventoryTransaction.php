<?php
namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class InventoryTransaction extends BaseModel
{
    protected string $table = 'inventory_transactions';
    protected array $fillable = [
        'part_id', 'transaction_type', 'quantity', 
        'reference_type', 'reference_id', 'unit_price', 'notes', 'created_by',
        'has_core', 'core_cost', 'core_rebate_expected', 'core_rebate_received',
        'location_key', 'location_aisle', 'location_shelf', 'location_bay', 'location_alt',
        'created_at'
    ];
    private static bool $schemaChecked = false;

    public function __construct()
    {
        $this->ensureSchema();
    }
    
    public function recordIncoming(
        int $partId, 
        int $quantity, 
        ?int $supplierId = null, 
        float $unitPrice = 0, 
        ?string $notes = null, 
        ?string $createdBy = null,
        bool $hasCore = false,
        float $coreCost = 0,
        float $coreRebateExpected = 0,
        float $coreRebateReceived = 0,
        string $referenceType = 'vendor',
        array $location = []
    ): array
    {
        return $this->create([
            'part_id' => $partId,
            'transaction_type' => 'incoming',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $supplierId,
            'unit_price' => $unitPrice,
            'notes' => $notes,
            'created_by' => $createdBy,
            'has_core' => $hasCore ? 1 : 0,
            'core_cost' => $coreCost,
            'core_rebate_expected' => $coreRebateExpected,
            'core_rebate_received' => $coreRebateReceived,
            'location_key' => $location['location_key'] ?? null,
            'location_aisle' => $location['location_aisle'] ?? null,
            'location_shelf' => $location['location_shelf'] ?? null,
            'location_bay' => $location['location_bay'] ?? null,
            'location_alt' => $location['location_alt'] ?? null,
        ]);
    }
    
    public function recordCheckout(
        int $partId,
        int $quantity,
        string $refType,
        int $refId,
        ?string $notes = null,
        ?string $createdBy = null,
        ?float $unitPrice = null,
        ?string $createdAt = null,
        array $location = []
    ): array
    {
        $payload = [
            'part_id' => $partId,
            'transaction_type' => 'outgoing',
            'quantity' => -$quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'unit_price' => $unitPrice ?? 0,
            'notes' => $notes,
            'created_by' => $createdBy,
            'location_key' => $location['location_key'] ?? null,
            'location_aisle' => $location['location_aisle'] ?? null,
            'location_shelf' => $location['location_shelf'] ?? null,
            'location_bay' => $location['location_bay'] ?? null,
            'location_alt' => $location['location_alt'] ?? null,
        ];

        if ($createdAt) {
            $payload['created_at'] = $createdAt;
        }

        return $this->create($payload);
    }
    
    public function recordReturn(
        int $partId,
        int $quantity,
        string $refType,
        int $refId,
        ?string $notes = null,
        ?string $createdBy = null,
        ?float $unitPrice = null,
        ?string $createdAt = null,
        array $location = []
    ): array
    {
        $payload = [
            'part_id' => $partId,
            'transaction_type' => 'return',
            'quantity' => $quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'unit_price' => $unitPrice ?? 0,
            'notes' => $notes,
            'created_by' => $createdBy,
            'location_key' => $location['location_key'] ?? null,
            'location_aisle' => $location['location_aisle'] ?? null,
            'location_shelf' => $location['location_shelf'] ?? null,
            'location_bay' => $location['location_bay'] ?? null,
            'location_alt' => $location['location_alt'] ?? null,
        ];

        if ($createdAt) {
            $payload['created_at'] = $createdAt;
        }

        return $this->create($payload);
    }
    
    public function search(array $filters = [], ?int $limit = 100): array
    {
        $sql = "SELECT it.*, 
                       p.fowler_part_number as part_number, 
                       p.supplier_part_number as supplier_part_number,
                       p.name as part_name,
                       COALESCE(
                           NULLIF(CONCAT_WS(' ', it.location_aisle, it.location_shelf, it.location_bay), ''),
                           NULLIF(it.location_alt, ''),
                           NULLIF(CONCAT_WS(' ', p.location_aisle, p.location_shelf, p.location_bay), ''),
                           p.location_alt
                       ) as location,
                       CASE 
                           WHEN it.reference_type = 'work_order' THEN wo.wo_number
                           WHEN it.reference_type = 'unit' THEN u.name
                           WHEN it.reference_type = 'technician' THEN t.name
                           WHEN it.reference_type IN ('supplier', 'vendor') THEN s.name
                           ELSE NULL
                       END as reference_number,
                       it.created_by as user_name
                FROM inventory_transactions it
                JOIN parts p ON it.part_id = p.id
                LEFT JOIN work_orders wo ON it.reference_type = 'work_order' AND it.reference_id = wo.id
                LEFT JOIN units u ON it.reference_type = 'unit' AND it.reference_id = u.id
                LEFT JOIN technicians t ON it.reference_type = 'technician' AND it.reference_id = t.id
                LEFT JOIN suppliers s ON (it.reference_type = 'supplier' OR it.reference_type = 'vendor') AND it.reference_id = s.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['part_number'])) {
            $sql .= " AND p.fowler_part_number LIKE ?";
            $params[] = "%" . $filters['part_number'] . "%";
        }

        $sql .= " ORDER BY it.created_at DESC, it.id DESC";
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        return Database::query($sql, $params)->fetchAll();
    }
    
    public function getByPart(int $partId): array
    {
        $sql = "SELECT it.*, 
                       p.fowler_part_number as part_number, 
                       p.supplier_part_number as supplier_part_number,
                       p.name as part_name,
                       COALESCE(
                           NULLIF(CONCAT_WS(' ', it.location_aisle, it.location_shelf, it.location_bay), ''),
                           NULLIF(it.location_alt, ''),
                           NULLIF(CONCAT_WS(' ', p.location_aisle, p.location_shelf, p.location_bay), ''),
                           p.location_alt
                       ) as location,
                       CASE 
                           WHEN it.reference_type = 'work_order' THEN wo.wo_number
                           WHEN it.reference_type = 'unit' THEN u.name
                           WHEN it.reference_type = 'technician' THEN t.name
                           WHEN it.reference_type IN ('supplier', 'vendor') THEN s.name
                           ELSE NULL
                       END as reference_number
                FROM inventory_transactions it
                JOIN parts p ON it.part_id = p.id
                LEFT JOIN work_orders wo ON it.reference_type = 'work_order' AND it.reference_id = wo.id
                LEFT JOIN units u ON it.reference_type = 'unit' AND it.reference_id = u.id
                LEFT JOIN technicians t ON it.reference_type = 'technician' AND it.reference_id = t.id
                LEFT JOIN suppliers s ON (it.reference_type = 'supplier' OR it.reference_type = 'vendor') AND it.reference_id = s.id
                WHERE it.part_id = ?
                ORDER BY it.created_at DESC, it.id DESC";
        return Database::query($sql, [$partId])->fetchAll();
    }
    
    public function getRecent(?int $limit = 50): array
    {
        $sql = "SELECT it.*, 
                       p.fowler_part_number as part_number, 
                       p.supplier_part_number as supplier_part_number,
                       p.name as part_name,
                       COALESCE(
                           NULLIF(CONCAT_WS(' ', it.location_aisle, it.location_shelf, it.location_bay), ''),
                           NULLIF(it.location_alt, ''),
                           NULLIF(CONCAT_WS(' ', p.location_aisle, p.location_shelf, p.location_bay), ''),
                           p.location_alt
                       ) as location,
                       CASE 
                           WHEN it.reference_type = 'work_order' THEN wo.wo_number
                           WHEN it.reference_type = 'unit' THEN u.name
                           WHEN it.reference_type = 'technician' THEN t.name
                           WHEN it.reference_type IN ('supplier', 'vendor') THEN s.name
                           ELSE NULL
                       END as reference_number,
                       it.created_by as user_name
                FROM inventory_transactions it
                JOIN parts p ON it.part_id = p.id
                LEFT JOIN work_orders wo ON it.reference_type = 'work_order' AND it.reference_id = wo.id
                LEFT JOIN units u ON it.reference_type = 'unit' AND it.reference_id = u.id
                LEFT JOIN technicians t ON it.reference_type = 'technician' AND it.reference_id = t.id
                LEFT JOIN suppliers s ON (it.reference_type = 'supplier' OR it.reference_type = 'vendor') AND it.reference_id = s.id
                ORDER BY it.created_at DESC, it.id DESC";
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ?";
            return Database::query($sql, [$limit])->fetchAll();
        }
        return Database::query($sql)->fetchAll();
    }

    private function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        try {
            $columns = [
                'location_key' => "ALTER TABLE {$this->table} ADD COLUMN location_key VARCHAR(255) NULL AFTER core_rebate_received",
                'location_aisle' => "ALTER TABLE {$this->table} ADD COLUMN location_aisle VARCHAR(20) NULL AFTER location_key",
                'location_shelf' => "ALTER TABLE {$this->table} ADD COLUMN location_shelf VARCHAR(20) NULL AFTER location_aisle",
                'location_bay' => "ALTER TABLE {$this->table} ADD COLUMN location_bay VARCHAR(20) NULL AFTER location_shelf",
                'location_alt' => "ALTER TABLE {$this->table} ADD COLUMN location_alt VARCHAR(255) NULL AFTER location_bay",
            ];

            foreach ($columns as $name => $sql) {
                $exists = Database::query(
                    "SELECT 1
                     FROM information_schema.columns
                     WHERE table_schema = DATABASE()
                       AND table_name = ?
                       AND column_name = ?
                     LIMIT 1",
                    [$this->table, $name]
                )->fetch();

                if (!$exists) {
                    Database::query($sql);
                }
            }

            self::$schemaChecked = true;
        } catch (\Throwable $e) {
            error_log('Inventory transaction schema check failed: ' . $e->getMessage());
        }
    }
}
