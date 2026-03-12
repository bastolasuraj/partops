<?php
namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class InventoryLocationLevel extends BaseModel
{
    protected string $table = 'inventory_location_levels';
    protected array $fillable = [
        'part_id',
        'location_key',
        'location_aisle',
        'location_shelf',
        'location_bay',
        'location_alt',
        'quantity',
    ];

    public function __construct()
    {
    }

    public function normalizeLocationPayload(array $payload = [], ?array $part = null): array
    {
        $locationRaw = $this->normalizeString($payload['location_raw'] ?? '');
        $locationAisle = $this->normalizeString($payload['location_aisle'] ?? '');
        $locationShelf = $this->normalizeString($payload['location_shelf'] ?? '');
        $locationBay = $this->normalizeString($payload['location_bay'] ?? '');
        $locationAlt = $this->normalizeString($payload['location_alt'] ?? '');

        if ($locationRaw !== '') {
            [$parsedAisle, $parsedShelf, $parsedBay, $parsedAlt] = $this->parseLocation($locationRaw);
            if ($parsedAisle !== '' || $parsedShelf !== '' || $parsedBay !== '') {
                $locationAisle = $parsedAisle;
                $locationShelf = $parsedShelf;
                $locationBay = $parsedBay;
                if ($locationAlt === '') {
                    $locationAlt = $parsedAlt;
                }
            } elseif ($locationAlt === '') {
                $locationAlt = $parsedAlt;
            }
        }

        if ($part) {
            if ($locationAisle === '') {
                $locationAisle = $this->normalizeString($part['location_aisle'] ?? '');
            }
            if ($locationShelf === '') {
                $locationShelf = $this->normalizeString($part['location_shelf'] ?? '');
            }
            if ($locationBay === '') {
                $locationBay = $this->normalizeString($part['location_bay'] ?? '');
            }
            if ($locationAlt === '') {
                $locationAlt = $this->normalizeString($part['location_alt'] ?? '');
            }
        }

        if ($locationAisle === '' && $locationShelf === '' && $locationBay === '' && $locationAlt === '') {
            $locationAlt = 'unassigned';
        }

        $locationKey = $this->buildLocationKey($locationAisle, $locationShelf, $locationBay, $locationAlt);

        return [
            'location_key' => $locationKey,
            'location_aisle' => $locationAisle,
            'location_shelf' => $locationShelf,
            'location_bay' => $locationBay,
            'location_alt' => $locationAlt,
            'location_display' => $this->buildLocationDisplay($locationAisle, $locationShelf, $locationBay, $locationAlt),
        ];
    }

    public function resolveRequestedLocationKey(array $payload = [], ?array $part = null): ?string
    {
        $providedKey = $this->normalizeString($payload['location_key'] ?? '');
        if ($providedKey !== '') {
            return $providedKey;
        }

        $hasLocationFields =
            $this->normalizeString($payload['location_raw'] ?? '') !== '' ||
            $this->normalizeString($payload['location_aisle'] ?? '') !== '' ||
            $this->normalizeString($payload['location_shelf'] ?? '') !== '' ||
            $this->normalizeString($payload['location_bay'] ?? '') !== '' ||
            $this->normalizeString($payload['location_alt'] ?? '') !== '';

        if (!$hasLocationFields) {
            return null;
        }

        $normalized = $this->normalizeLocationPayload($payload, $part);
        return $normalized['location_key'];
    }

    public function getByPart(int $partId, ?array $part = null): array
    {
        $this->seedFromLegacyIfNeeded($partId, $part);

        $rows = Database::query(
            "SELECT *
             FROM {$this->table}
             WHERE part_id = ?
               AND quantity > 0
             ORDER BY last_updated ASC, id ASC",
            [$partId]
        )->fetchAll();

        return array_map(fn ($row) => $this->decorateRow($row), $rows);
    }

    public function getLocationStock(int $partId, string $locationKey, ?array $part = null): int
    {
        $this->seedFromLegacyIfNeeded($partId, $part);

        $row = Database::query(
            "SELECT quantity
             FROM {$this->table}
             WHERE part_id = ?
               AND location_key = ?
             LIMIT 1",
            [$partId, $locationKey]
        )->fetch();

        return $row ? (int)$row['quantity'] : 0;
    }

    public function getTotalStock(int $partId, ?array $part = null): int
    {
        $this->seedFromLegacyIfNeeded($partId, $part);
        $row = Database::query(
            "SELECT COALESCE(SUM(quantity), 0) as total_quantity
             FROM {$this->table}
             WHERE part_id = ?",
            [$partId]
        )->fetch();

        return (int)($row['total_quantity'] ?? 0);
    }

    public function addStock(int $partId, int $quantity, array $locationPayload = [], ?array $part = null): ?array
    {
        if ($quantity <= 0) {
            return null;
        }

        $location = $this->normalizeLocationPayload($locationPayload, $part);

        Database::query(
            "INSERT INTO {$this->table}
                 (part_id, location_key, location_aisle, location_shelf, location_bay, location_alt, quantity)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 quantity = quantity + VALUES(quantity),
                 location_aisle = VALUES(location_aisle),
                 location_shelf = VALUES(location_shelf),
                 location_bay = VALUES(location_bay),
                 location_alt = VALUES(location_alt),
                 last_updated = CURRENT_TIMESTAMP",
            [
                $partId,
                $location['location_key'],
                $location['location_aisle'],
                $location['location_shelf'],
                $location['location_bay'],
                $location['location_alt'],
                $quantity,
            ]
        );

        $row = Database::query(
            "SELECT *
             FROM {$this->table}
             WHERE part_id = ?
               AND location_key = ?
             LIMIT 1",
            [$partId, $location['location_key']]
        )->fetch();

        return $row ? $this->decorateRow($row) : null;
    }

    public function removeStock(int $partId, int $quantity, array $locationPayload = [], ?array $part = null): array
    {
        if ($quantity <= 0) {
            return [
                'removed_quantity' => 0,
                'breakdown' => [],
            ];
        }

        $this->seedFromLegacyIfNeeded($partId, $part);

        $requestedLocationKey = $this->resolveRequestedLocationKey($locationPayload, $part);
        if ($requestedLocationKey !== null) {
            return $this->removeFromSpecificLocation($partId, $quantity, $requestedLocationKey);
        }

        return $this->removeFromAnyLocation($partId, $quantity);
    }

    private function removeFromSpecificLocation(int $partId, int $quantity, string $locationKey): array
    {
        $row = Database::query(
            "SELECT *
             FROM {$this->table}
             WHERE part_id = ?
               AND location_key = ?
             LIMIT 1
             FOR UPDATE",
            [$partId, $locationKey]
        )->fetch();

        $available = $row ? (int)$row['quantity'] : 0;
        if ($available < $quantity) {
            throw new \RuntimeException("Insufficient stock at selected location (available {$available}, requested {$quantity})");
        }

        $newQty = $available - $quantity;
        Database::query(
            "UPDATE {$this->table}
             SET quantity = ?,
                 last_updated = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$newQty, $row['id']]
        );

        return [
            'removed_quantity' => $quantity,
            'breakdown' => [
                [
                    'location_key' => (string)$row['location_key'],
                    'location_aisle' => (string)($row['location_aisle'] ?? ''),
                    'location_shelf' => (string)($row['location_shelf'] ?? ''),
                    'location_bay' => (string)($row['location_bay'] ?? ''),
                    'location_alt' => (string)($row['location_alt'] ?? ''),
                    'location_display' => $this->buildLocationDisplay(
                        (string)($row['location_aisle'] ?? ''),
                        (string)($row['location_shelf'] ?? ''),
                        (string)($row['location_bay'] ?? ''),
                        (string)($row['location_alt'] ?? '')
                    ),
                    'quantity' => $quantity,
                    'remaining_quantity' => $newQty,
                ],
            ],
        ];
    }

    private function removeFromAnyLocation(int $partId, int $quantity): array
    {
        $rows = Database::query(
            "SELECT *
             FROM {$this->table}
             WHERE part_id = ?
               AND quantity > 0
             ORDER BY last_updated ASC, id ASC
             FOR UPDATE",
            [$partId]
        )->fetchAll();

        if (count($rows) > 1) {
            throw new \RuntimeException(
                'This part is stocked in multiple locations. Select one location and use separate transactions for additional locations.'
            );
        }

        $remaining = $quantity;
        $breakdown = [];

        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $available = (int)$row['quantity'];
            if ($available <= 0) {
                continue;
            }

            $deduct = min($available, $remaining);
            $newQty = $available - $deduct;
            Database::query(
                "UPDATE {$this->table}
                 SET quantity = ?,
                     last_updated = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$newQty, $row['id']]
            );

            $breakdown[] = [
                'location_key' => (string)$row['location_key'],
                'location_aisle' => (string)($row['location_aisle'] ?? ''),
                'location_shelf' => (string)($row['location_shelf'] ?? ''),
                'location_bay' => (string)($row['location_bay'] ?? ''),
                'location_alt' => (string)($row['location_alt'] ?? ''),
                'location_display' => $this->buildLocationDisplay(
                    (string)($row['location_aisle'] ?? ''),
                    (string)($row['location_shelf'] ?? ''),
                    (string)($row['location_bay'] ?? ''),
                    (string)($row['location_alt'] ?? '')
                ),
                'quantity' => $deduct,
                'remaining_quantity' => $newQty,
            ];

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Insufficient distributed stock (missing {$remaining})");
        }

        return [
            'removed_quantity' => $quantity,
            'breakdown' => $breakdown,
        ];
    }

    private function seedFromLegacyIfNeeded(int $partId, ?array $part = null): void
    {
        $row = Database::query(
            "SELECT COUNT(*) as count_rows
             FROM {$this->table}
             WHERE part_id = ?",
            [$partId]
        )->fetch();

        if ((int)($row['count_rows'] ?? 0) > 0) {
            return;
        }

        $stockRow = Database::query(
            "SELECT COALESCE(quantity, 0) as stock
             FROM inventory_levels
             WHERE part_id = ?
             LIMIT 1",
            [$partId]
        )->fetch();

        $stock = (int)($stockRow['stock'] ?? 0);
        if ($stock <= 0) {
            return;
        }

        if ($part === null) {
            $part = Database::query(
                "SELECT location_aisle, location_shelf, location_bay, location_alt
                 FROM parts
                 WHERE id = ?
                 LIMIT 1",
                [$partId]
            )->fetch() ?: [];
        }

        $location = $this->normalizeLocationPayload([], $part);
        Database::query(
            "INSERT INTO {$this->table}
                 (part_id, location_key, location_aisle, location_shelf, location_bay, location_alt, quantity)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $partId,
                $location['location_key'],
                $location['location_aisle'],
                $location['location_shelf'],
                $location['location_bay'],
                $location['location_alt'],
                $stock,
            ]
        );
    }

    private function buildLocationKey(string $aisle, string $shelf, string $bay, string $alt): string
    {
        $a = strtolower($this->normalizeString($aisle));
        $s = strtolower($this->normalizeString($shelf));
        $b = strtolower($this->normalizeString($bay));
        $t = strtolower($this->normalizeString($alt));

        if ($a !== '' || $s !== '' || $b !== '') {
            return 'rack:' . implode('|', [$a, $s, $b]);
        }

        return 'alt:' . ($t !== '' ? $t : 'unassigned');
    }

    private function buildLocationDisplay(string $aisle, string $shelf, string $bay, string $alt): string
    {
        $a = $this->normalizeString($aisle);
        $s = $this->normalizeString($shelf);
        $b = $this->normalizeString($bay);
        $t = $this->normalizeString($alt);

        if ($a !== '' || $s !== '' || $b !== '') {
            $parts = array_filter([$a, $s, $b], fn ($value) => $value !== '');
            if (!empty($parts)) {
                return implode('-', $parts);
            }
        }

        return $t !== '' ? $t : 'unassigned';
    }

    private function parseLocation(string $raw): array
    {
        $clean = $this->normalizeString($raw);
        if ($clean === '') {
            return ['', '', '', ''];
        }

        if (preg_match('/^[^\\-\\.]+-[^\\-\\.]+-[^\\-\\.]+$/', $clean)) {
            $tokens = array_map('trim', explode('-', $clean));
            return [$tokens[0] ?? '', $tokens[1] ?? '', $tokens[2] ?? '', ''];
        }

        if (preg_match('/^[^\\.]+\\.[^\\.]+\\.[^\\.]+$/', $clean)) {
            $tokens = array_map('trim', explode('.', $clean));
            return [$tokens[0] ?? '', $tokens[1] ?? '', $tokens[2] ?? '', ''];
        }

        return ['', '', '', $clean];
    }

    private function normalizeString($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', (string)$value));
    }

    private function decorateRow(array $row): array
    {
        $row['location_display'] = $this->buildLocationDisplay(
            (string)($row['location_aisle'] ?? ''),
            (string)($row['location_shelf'] ?? ''),
            (string)($row['location_bay'] ?? ''),
            (string)($row['location_alt'] ?? '')
        );

        return $row;
    }

}

