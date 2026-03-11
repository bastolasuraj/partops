<?php
namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;
use DateTime; // Import DateTime class

class Part extends BaseModel
{
    protected string $table = 'parts';
    protected array $fillable = [
        'fowler_part_number', 'name', 'description', 'supplier_id', 
        'supplier_part_number', 'unit_of_measure', 'location_aisle', 'location_shelf', 
        'location_bay', 'location_alt', 'low_stock_threshold', 'has_core', 
        'core_cost', 'core_rebate', 'unit_price'
    ];
    
    // Override find to exclude soft-deleted items
    public function find(int $id): ?array
    {
        $stmt = Database::query(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND deleted_at IS NULL",
            [$id]
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getAllWithDetails(?string $search = null): array
    {
        $sql = "SELECT p.*, 
                       s.name as supplier_name,
                       COALESCE(il.quantity, 0) as stock,
                       COALESCE(ills.location_stock_summary, '') as location_stock_summary,
                       COALESCE(ills.location_count, 0) as location_count,
                       (COALESCE(il.quantity, 0) * COALESCE(p.unit_price, 0)) as total_cost
                FROM parts p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                LEFT JOIN (
                    SELECT part_id,
                           GROUP_CONCAT(
                               CONCAT(
                                   CASE
                                       WHEN COALESCE(location_aisle, '') <> '' OR COALESCE(location_shelf, '') <> '' OR COALESCE(location_bay, '') <> ''
                                           THEN CONCAT_WS('-', location_aisle, location_shelf, location_bay)
                                       WHEN COALESCE(location_alt, '') <> ''
                                           THEN location_alt
                                       ELSE 'unassigned'
                                   END,
                                   ' (',
                                   quantity,
                                   ')'
                               )
                               ORDER BY last_updated ASC, id ASC
                               SEPARATOR ', '
                           ) as location_stock_summary,
                           COUNT(*) as location_count
                    FROM inventory_location_levels
                    WHERE quantity > 0
                    GROUP BY part_id
                ) ills ON p.id = ills.part_id
                WHERE p.deleted_at IS NULL";

        $params = [];
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.fowler_part_number LIKE ? OR p.supplier_part_number LIKE ? OR s.name LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }

        $sql .= " ORDER BY p.supplier_part_number"; // Order by the new first column
        return Database::query($sql, $params)->fetchAll();
    }
    
    public function findWithDetails(int $id): ?array
    {
        $sql = "SELECT p.*, 
                       s.name as supplier_name,
                       COALESCE(il.quantity, 0) as stock,
                       (COALESCE(il.quantity, 0) * COALESCE(p.unit_price, 0)) as total_cost
                FROM parts p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                WHERE p.id = ? AND p.deleted_at IS NULL"; // Exclude soft-deleted
        $result = Database::query($sql, [$id])->fetch();
        return $result ?: null;
    }
    
    public function findBySupplierPartNumber(string $supplierPartNumber): ?array
    {
        $stmt = Database::query( // Changed to use direct query to include deleted_at filter
            "SELECT * FROM {$this->table} WHERE supplier_part_number = ? AND deleted_at IS NULL LIMIT 1",
            [$supplierPartNumber]
        );
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function findByFowlerPartNumber(string $fowlerPartNumber): array
    {
        $sql = "SELECT p.*, 
                       s.name as supplier_name,
                       COALESCE(il.quantity, 0) as stock,
                       (COALESCE(il.quantity, 0) * COALESCE(p.unit_price, 0)) as total_cost
                FROM parts p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                WHERE p.fowler_part_number = ? AND p.deleted_at IS NULL"; // Exclude soft-deleted
        $result = Database::query($sql, [$fowlerPartNumber])->fetchAll();
        return $result ?: [];
    }
    
    public function getLowStock(): array
    {
        $sql = "SELECT p.*, 
                       s.name as supplier_name,
                       COALESCE(il.quantity, 0) as stock
                FROM parts p
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                LEFT JOIN inventory_levels il ON p.id = il.part_id
                WHERE COALESCE(il.quantity, 0) <= p.low_stock_threshold AND p.deleted_at IS NULL"; // Exclude soft-deleted
        return Database::query($sql)->fetchAll();
    }
    
    public function getStock(int $partId): int
    {
        $sql = "SELECT COALESCE(quantity, 0) as stock FROM inventory_levels WHERE part_id = ?";
        $result = Database::query($sql, [$partId])->fetch();
        return $result ? (int)$result['stock'] : 0;
    }
    
    public function updateStock(int $partId, int $quantity): void
    {
        $sql = "INSERT INTO inventory_levels (part_id, quantity) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE quantity = ?";
        Database::query($sql, [$partId, $quantity, $quantity]);
    }
    
    public function adjustStock(int $partId, int $adjustment): int
    {
        $currentStock = $this->getStock($partId);
        $newStock = max(0, $currentStock + $adjustment);
        $this->updateStock($partId, $newStock);
        return $newStock;
    }

    public function getFifoIssueUnitPrice(int $partId, int $quantity): float
    {
        $requestedQty = max(0, $quantity);
        if ($requestedQty === 0) {
            return 0.0;
        }

        $breakdown = $this->getFifoIssueBreakdown($partId, $requestedQty);
        if ($this->sumBreakdownQuantity($breakdown) === 0) {
            return round($this->getPartUnitPrice($partId), 2);
        }

        $totalValue = $this->sumBreakdownValue($breakdown);

        return round($totalValue / $requestedQty, 2);
    }

    public function getFifoIssueBreakdown(int $partId, int $quantity): array
    {
        $requestedQty = max(0, $quantity);
        if ($requestedQty === 0) {
            return [];
        }

        $fallbackPrice = $this->getPartUnitPrice($partId);
        $currentStock = $this->getStock($partId);
        $layers = $this->normalizeLayersToCurrentStock(
            $this->buildFifoLayers($partId),
            $currentStock,
            $fallbackPrice
        );
        $consumed = $this->consumeLayersWithBreakdown($layers, $requestedQty);
        $breakdown = $consumed['breakdown'];

        if ($consumed['consumed_quantity'] < $requestedQty) {
            $missingQty = $requestedQty - $consumed['consumed_quantity'];
            if ($missingQty > 0) {
                $breakdown[] = [
                    'quantity' => $missingQty,
                    'unit_price' => round($fallbackPrice, 2),
                ];
            }
        }

        return $this->mergeBreakdownByUnitPrice($breakdown);
    }

    public function getReferenceOutstandingIssueUnitPrice(
        int $partId,
        string $referenceType,
        int $referenceId,
        int $quantity
    ): float {
        $requestedQty = max(0, $quantity);
        if ($requestedQty === 0) {
            return 0.0;
        }

        $sql = "SELECT transaction_type, quantity, unit_price
                FROM inventory_transactions
                WHERE part_id = ?
                  AND reference_type = ?
                  AND reference_id = ?
                  AND transaction_type IN ('outgoing', 'return')
                ORDER BY created_at ASC, id ASC";

        $transactions = Database::query($sql, [$partId, $referenceType, $referenceId])->fetchAll();
        $fallbackPrice = $this->getPartUnitPrice($partId);
        $outstanding = [];

        foreach ($transactions as $transaction) {
            $transactionType = (string)($transaction['transaction_type'] ?? '');
            $transactionQty = (int)($transaction['quantity'] ?? 0);
            $transactionUnitPrice = (float)($transaction['unit_price'] ?? 0);

            if ($transactionType === 'outgoing' && $transactionQty < 0) {
                $issuedQty = abs($transactionQty);
                $outstandingAverage = $this->calculateAverageLayerCost($outstanding);
                $issuedUnitPrice = $transactionUnitPrice > 0
                    ? $transactionUnitPrice
                    : ($outstandingAverage > 0 ? $outstandingAverage : $fallbackPrice);
                $this->appendLayer($outstanding, $issuedQty, $issuedUnitPrice);
                continue;
            }

            if ($transactionType === 'return' && $transactionQty > 0) {
                $this->consumeLayers($outstanding, $transactionQty);
            }
        }

        $consumed = $this->consumeLayers($outstanding, $requestedQty);
        if ($consumed['consumed_quantity'] < $requestedQty) {
            $missingQty = $requestedQty - $consumed['consumed_quantity'];
            $consumed['consumed_value'] += $missingQty * $fallbackPrice;
            $consumed['consumed_quantity'] = $requestedQty;
        }

        if ($consumed['consumed_quantity'] <= 0) {
            return round($fallbackPrice, 2);
        }

        return round($consumed['consumed_value'] / $requestedQty, 2);
    }

    public function syncUnitPriceFromFifo(int $partId): float
    {
        $part = $this->find($partId);
        if (!$part) {
            return 0.0;
        }

        $fallbackPrice = max(0, (float)($part['unit_price'] ?? 0));
        $currentStock = $this->getStock($partId);
        $layers = $this->normalizeLayersToCurrentStock(
            $this->buildFifoLayers($partId),
            $currentStock,
            $fallbackPrice
        );
        $unitPrice = round($this->calculateAverageLayerCost($layers), 2);
        $this->update($partId, ['unit_price' => $unitPrice]);

        return $unitPrice;
    }

    private function buildFifoLayers(int $partId): array
    {
        $sql = "SELECT transaction_type, quantity, unit_price
                FROM inventory_transactions
                WHERE part_id = ?
                ORDER BY created_at ASC, id ASC";
        $transactions = Database::query($sql, [$partId])->fetchAll();

        $layers = [];
        $fallbackPrice = $this->getPartUnitPrice($partId);

        foreach ($transactions as $transaction) {
            $transactionType = (string)($transaction['transaction_type'] ?? '');
            $transactionQty = (int)($transaction['quantity'] ?? 0);
            $transactionUnitPrice = (float)($transaction['unit_price'] ?? 0);

            if (($transactionType === 'incoming' || $transactionType === 'return') && $transactionQty > 0) {
                $averageCost = $this->calculateAverageLayerCost($layers);
                $incomingUnitPrice = $transactionUnitPrice > 0
                    ? $transactionUnitPrice
                    : ($averageCost > 0 ? $averageCost : $fallbackPrice);

                $this->appendLayer($layers, $transactionQty, $incomingUnitPrice);
                continue;
            }

            if ($transactionType === 'outgoing' && $transactionQty < 0) {
                $this->consumeLayers($layers, abs($transactionQty));
                continue;
            }

            if ($transactionQty < 0) {
                $this->consumeLayers($layers, abs($transactionQty));
                continue;
            }

            if ($transactionQty > 0) {
                $averageCost = $this->calculateAverageLayerCost($layers);
                $positiveUnitPrice = $transactionUnitPrice > 0
                    ? $transactionUnitPrice
                    : ($averageCost > 0 ? $averageCost : $fallbackPrice);
                $this->appendLayer($layers, $transactionQty, $positiveUnitPrice);
            }
        }

        return $layers;
    }

    private function appendLayer(array &$layers, int $quantity, float $unitPrice): void
    {
        $safeQty = max(0, $quantity);
        if ($safeQty === 0) {
            return;
        }

        $layers[] = [
            'quantity' => $safeQty,
            'unit_price' => max(0, $unitPrice),
        ];
    }

    private function consumeLayers(array &$layers, int $quantity): array
    {
        $consumed = $this->consumeLayersWithBreakdown($layers, $quantity);

        return [
            'consumed_quantity' => $consumed['consumed_quantity'],
            'consumed_value' => $consumed['consumed_value'],
        ];
    }

    private function consumeLayersWithBreakdown(array &$layers, int $quantity): array
    {
        $remainingQty = max(0, $quantity);
        $consumedQty = 0;
        $consumedValue = 0.0;
        $breakdown = [];

        while ($remainingQty > 0 && !empty($layers)) {
            $layerQty = (int)($layers[0]['quantity'] ?? 0);
            $layerUnitPrice = (float)($layers[0]['unit_price'] ?? 0);

            if ($layerQty <= 0) {
                array_shift($layers);
                continue;
            }

            $takeQty = min($layerQty, $remainingQty);
            $safeUnitPrice = round(max(0, $layerUnitPrice), 2);
            $consumedQty += $takeQty;
            $consumedValue += $takeQty * $safeUnitPrice;
            $breakdown[] = [
                'quantity' => $takeQty,
                'unit_price' => $safeUnitPrice,
            ];

            $remainingQty -= $takeQty;
            $layerQty -= $takeQty;

            if ($layerQty === 0) {
                array_shift($layers);
            } else {
                $layers[0]['quantity'] = $layerQty;
            }
        }

        return [
            'consumed_quantity' => $consumedQty,
            'consumed_value' => $consumedValue,
            'breakdown' => $this->mergeBreakdownByUnitPrice($breakdown),
        ];
    }

    private function mergeBreakdownByUnitPrice(array $breakdown): array
    {
        $merged = [];

        foreach ($breakdown as $layer) {
            $qty = max(0, (int)($layer['quantity'] ?? 0));
            if ($qty === 0) {
                continue;
            }

            $unitPrice = round(max(0, (float)($layer['unit_price'] ?? 0)), 2);
            $lastIndex = count($merged) - 1;

            if ($lastIndex >= 0 && (float)$merged[$lastIndex]['unit_price'] === $unitPrice) {
                $merged[$lastIndex]['quantity'] += $qty;
                continue;
            }

            $merged[] = [
                'quantity' => $qty,
                'unit_price' => $unitPrice,
            ];
        }

        return $merged;
    }

    private function sumBreakdownQuantity(array $breakdown): int
    {
        $totalQty = 0;

        foreach ($breakdown as $layer) {
            $qty = (int)($layer['quantity'] ?? 0);
            if ($qty > 0) {
                $totalQty += $qty;
            }
        }

        return $totalQty;
    }

    private function sumBreakdownValue(array $breakdown): float
    {
        $totalValue = 0.0;

        foreach ($breakdown as $layer) {
            $qty = (int)($layer['quantity'] ?? 0);
            $unitPrice = (float)($layer['unit_price'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $totalValue += $qty * max(0, $unitPrice);
        }

        return $totalValue;
    }

    private function calculateAverageLayerCost(array $layers): float
    {
        $totalQty = 0;
        $totalValue = 0.0;

        foreach ($layers as $layer) {
            $qty = (int)($layer['quantity'] ?? 0);
            $unitPrice = (float)($layer['unit_price'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $totalQty += $qty;
            $totalValue += $qty * max(0, $unitPrice);
        }

        if ($totalQty <= 0) {
            return 0.0;
        }

        return $totalValue / $totalQty;
    }

    private function normalizeLayersToCurrentStock(array $layers, int $currentStock, float $fallbackPrice): array
    {
        $normalizedLayers = $layers;
        $actualStock = max(0, $currentStock);
        $layerStock = $this->sumLayerQuantity($normalizedLayers);

        if ($layerStock > $actualStock) {
            $this->consumeLayers($normalizedLayers, $layerStock - $actualStock);
            $layerStock = $this->sumLayerQuantity($normalizedLayers);
        }

        if ($layerStock < $actualStock) {
            $missingStock = $actualStock - $layerStock;
            $averageCost = $this->calculateAverageLayerCost($normalizedLayers);
            $imputedUnitPrice = $fallbackPrice > 0 ? $fallbackPrice : $averageCost;
            $this->appendLayer($normalizedLayers, $missingStock, $imputedUnitPrice);
        }

        return $normalizedLayers;
    }

    private function sumLayerQuantity(array $layers): int
    {
        $totalQty = 0;

        foreach ($layers as $layer) {
            $qty = (int)($layer['quantity'] ?? 0);
            if ($qty > 0) {
                $totalQty += $qty;
            }
        }

        return $totalQty;
    }

    private function getPartUnitPrice(int $partId): float
    {
        $part = $this->find($partId);
        if (!$part) {
            return 0.0;
        }

        return max(0, (float)($part['unit_price'] ?? 0));
    }

    // Override delete method for soft deletion with stock check
    public function delete(int $id): bool
    {
        $stock = $this->getStock($id);
        if ($stock > 0) {
            throw new \Exception("Cannot archive part with existing stock. Current stock: {$stock}");
        }

        $now = new DateTime();
        $stmt = Database::query(
            "UPDATE {$this->table} SET deleted_at = ? WHERE {$this->primaryKey} = ?",
            [$now->format('Y-m-d H:i:s'), $id]
        );
        return $stmt->rowCount() > 0;
    }
}
