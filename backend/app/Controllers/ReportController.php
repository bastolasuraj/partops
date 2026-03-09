<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Response;
use DateTime;

class ReportController extends BaseController
{
    public function summary(): void
    {
        $this->requireAuth();

        [$dateFrom, $dateTo, $fromTs, $toTs] = $this->resolveDateRange();

        Response::success([
            'generated_at' => date('Y-m-d H:i:s'),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'users' => $this->buildUserReport($fromTs, $toTs),
            'suppliers' => $this->buildSupplierReport($fromTs, $toTs),
            'transactions' => $this->buildTransactionReport($fromTs, $toTs),
            'parts' => $this->buildPartReport($fromTs, $toTs),
        ]);
    }

    public function archiveDownload(): void
    {
        $this->requireAuth();

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            Response::error('Report file is required', 422);
        }

        $file = $_FILES['file'];
        $errorCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            Response::error($this->uploadErrorMessage($errorCode), 422);
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            Response::error('Invalid uploaded report file', 422);
        }

        $sizeBytes = (int)($file['size'] ?? 0);
        if ($sizeBytes <= 0) {
            Response::error('Uploaded report file is empty', 422);
        }

        $maxBytes = 25 * 1024 * 1024;
        if ($sizeBytes > $maxBytes) {
            Response::error('Report file exceeds 25MB limit', 422);
        }

        $allowedMimeTypes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $requestMimeType = (string)($this->request->get('mime_type') ?? '');
        $detectedMimeType = (string)(mime_content_type($tmpName) ?: '');
        $mimeType = in_array($requestMimeType, $allowedMimeTypes, true) ? $requestMimeType : $detectedMimeType;

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            Response::error('Unsupported report file type', 422);
        }

        $extension = $mimeType === 'application/pdf' ? 'pdf' : 'xlsx';
        $reportScope = $this->normalizeReportScope((string)($this->request->get('report_scope') ?? 'all'));
        $originalName = (string)($this->request->get('file_name') ?? ($file['name'] ?? 'pam_reports'));
        $safeBaseName = $this->sanitizeFileBaseName(pathinfo($originalName, PATHINFO_FILENAME));
        if ($safeBaseName === '') {
            $safeBaseName = 'pam_reports';
        }

        $archiveDir = $this->ensureArchiveDirectory();
        $timestamp = date('Ymd_His');
        $archiveFileName = "{$safeBaseName}_{$reportScope}_{$timestamp}.{$extension}";
        $archivePath = $archiveDir . DIRECTORY_SEPARATOR . $archiveFileName;
        $dedupeCounter = 1;

        while (file_exists($archivePath)) {
            $archiveFileName = "{$safeBaseName}_{$reportScope}_{$timestamp}_{$dedupeCounter}.{$extension}";
            $archivePath = $archiveDir . DIRECTORY_SEPARATOR . $archiveFileName;
            $dedupeCounter++;
        }

        if (!move_uploaded_file($tmpName, $archivePath)) {
            Response::error('Failed to archive report copy', 500);
        }

        $user = $_SESSION['user'] ?? [];
        $manifestEntry = [
            'archived_at' => date('Y-m-d H:i:s'),
            'archive_file_name' => $archiveFileName,
            'archive_relative_path' => 'storage/report-downloads/' . date('Y-m') . '/' . $archiveFileName,
            'original_file_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'report_scope' => $reportScope,
            'date_from' => (string)($this->request->get('date_from') ?? ''),
            'date_to' => (string)($this->request->get('date_to') ?? ''),
            'user' => [
                'id' => $user['id'] ?? null,
                'username' => $user['username'] ?? '',
                'display_name' => $user['display_name'] ?? '',
                'auth_type' => $user['auth_type'] ?? '',
            ],
        ];

        $this->appendArchiveManifest($manifestEntry);

        Response::created([
            'archive_file_name' => $archiveFileName,
            'archive_relative_path' => $manifestEntry['archive_relative_path'],
            'size_bytes' => $sizeBytes,
            'report_scope' => $reportScope,
        ], 'Report copy archived');
    }

    public function archiveList(): void
    {
        $this->requireAuth();

        $limit = (int)$this->request->query('limit', 200);
        if ($limit <= 0) {
            $limit = 200;
        }
        $limit = min($limit, 500);

        $scope = $this->normalizeReportScope((string)$this->request->query('scope', 'all'));
        $manifestPath = $this->archiveManifestPath();

        if (!is_file($manifestPath)) {
            Response::success([
                'records' => [],
                'count' => 0,
                'scope' => $scope,
                'limit' => $limit,
            ], 'Archive list loaded');
        }

        $lines = @file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            Logger::warning('Failed to read report archive manifest', ['path' => $manifestPath]);
            Response::error('Failed to read report archive list', 500);
        }

        $records = [];
        for ($index = count($lines) - 1; $index >= 0; $index--) {
            if (count($records) >= $limit) {
                break;
            }

            $line = trim((string)$lines[$index]);
            if ($line === '') {
                continue;
            }

            $entry = json_decode($line, true);
            if (!is_array($entry)) {
                continue;
            }

            $entryScope = $this->normalizeReportScope((string)($entry['report_scope'] ?? 'all'));
            if ($scope !== 'all' && $entryScope !== $scope) {
                continue;
            }

            $relativePath = (string)($entry['archive_relative_path'] ?? '');
            $resolvedPath = $this->resolveArchivePath($relativePath);
            $available = $resolvedPath !== null && is_file($resolvedPath);
            $user = is_array($entry['user'] ?? null) ? $entry['user'] : [];

            $records[] = [
                'archived_at' => (string)($entry['archived_at'] ?? ''),
                'archive_file_name' => (string)($entry['archive_file_name'] ?? ''),
                'archive_relative_path' => $relativePath,
                'original_file_name' => (string)($entry['original_file_name'] ?? ''),
                'mime_type' => (string)($entry['mime_type'] ?? ''),
                'size_bytes' => (int)($entry['size_bytes'] ?? 0),
                'report_scope' => $entryScope,
                'date_from' => (string)($entry['date_from'] ?? ''),
                'date_to' => (string)($entry['date_to'] ?? ''),
                'available' => $available,
                'user' => [
                    'id' => $user['id'] ?? null,
                    'username' => (string)($user['username'] ?? ''),
                    'display_name' => (string)($user['display_name'] ?? ''),
                    'auth_type' => (string)($user['auth_type'] ?? ''),
                ],
            ];
        }

        Response::success([
            'records' => $records,
            'count' => count($records),
            'scope' => $scope,
            'limit' => $limit,
        ], 'Archive list loaded');
    }

    public function archiveFile(): void
    {
        $this->requireAuth();

        $requestedPath = (string)$this->request->query('path', '');
        if (trim($requestedPath) === '') {
            Response::error('Archive path is required', 422);
        }

        $archivePath = $this->resolveArchivePath($requestedPath);
        if ($archivePath === null || !is_file($archivePath)) {
            Response::error('Archived report not found', 404);
        }

        $fileName = basename($archivePath);
        $extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
        $safeName = $this->sanitizeFileBaseName(pathinfo($fileName, PATHINFO_FILENAME));
        if ($safeName === '') {
            $safeName = 'pam_report';
        }
        if ($extension !== '') {
            $safeName .= '.' . $extension;
        }

        $mimeType = (string)(mime_content_type($archivePath) ?: 'application/octet-stream');
        $contentLength = (int)filesize($archivePath);
        $encodedName = rawurlencode($safeName);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(200);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $contentLength);
        header('Content-Disposition: attachment; filename="' . $safeName . '"; filename*=UTF-8\'\'' . $encodedName);
        header('Cache-Control: private, max-age=0, must-revalidate');

        readfile($archivePath);
        exit;
    }

    private function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            Response::error('Not authenticated', 401);
        }
    }

    private function resolveDateRange(): array
    {
        $dateFrom = $this->normalizeDate($this->request->query('date_from'), 'date_from');
        $dateTo = $this->normalizeDate($this->request->query('date_to'), 'date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            Response::error('date_from cannot be after date_to', 422);
        }

        $fromTs = $dateFrom ? ($dateFrom . ' 00:00:00') : null;
        $toTs = $dateTo ? ($dateTo . ' 23:59:59') : null;

        return [$dateFrom, $dateTo, $fromTs, $toTs];
    }

    private function normalizeDate($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            Response::error("{$field} must be a date string in YYYY-MM-DD format", 422);
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        $errors = DateTime::getLastErrors();

        if (!$date || ($errors && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))) {
            Response::error("{$field} must be in YYYY-MM-DD format", 422);
        }

        return $date->format('Y-m-d');
    }

    private function buildDateClause(string $column, ?string $fromTs, ?string $toTs): array
    {
        $parts = [];
        $params = [];

        if ($fromTs !== null) {
            $parts[] = "{$column} >= ?";
            $params[] = $fromTs;
        }

        if ($toTs !== null) {
            $parts[] = "{$column} <= ?";
            $params[] = $toTs;
        }

        if (empty($parts)) {
            return ['', []];
        }

        return [' AND ' . implode(' AND ', $parts), $params];
    }

    private function buildUserReport(?string $fromTs, ?string $toTs): array
    {
        [$dateSql, $params] = $this->buildDateClause('it.created_at', $fromTs, $toTs);
        $userExpr = "COALESCE(NULLIF(TRIM(it.created_by), ''), 'System')";

        $summarySql = "SELECT
                {$userExpr} AS user_name,
                COUNT(*) AS transaction_count,
                SUM(CASE WHEN it.transaction_type = 'incoming' THEN it.quantity ELSE 0 END) AS incoming_qty,
                SUM(CASE WHEN it.transaction_type = 'incoming' THEN it.quantity * COALESCE(it.unit_price, 0) ELSE 0 END) AS incoming_cost,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) ELSE 0 END) AS taken_qty,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) * COALESCE(it.unit_price, 0) ELSE 0 END) AS taken_cost,
                SUM(CASE WHEN it.transaction_type = 'return' THEN it.quantity ELSE 0 END) AS returned_qty,
                SUM(CASE WHEN it.transaction_type = 'return' THEN it.quantity * COALESCE(it.unit_price, 0) ELSE 0 END) AS returned_cost
            FROM inventory_transactions it
            WHERE 1 = 1 {$dateSql}
            GROUP BY {$userExpr}
            ORDER BY {$userExpr}";

        $rows = Database::query($summarySql, $params)->fetchAll();

        $summary = [];
        $totals = [
            'user_count' => 0,
            'transaction_count' => 0,
            'incoming_qty' => 0,
            'incoming_cost' => 0.0,
            'taken_qty' => 0,
            'taken_cost' => 0.0,
            'returned_qty' => 0,
            'returned_cost' => 0.0,
            'not_returned_qty' => 0,
            'not_returned_cost' => 0.0,
        ];

        foreach ($rows as $row) {
            $takenQty = $this->toInt($row['taken_qty'] ?? 0);
            $takenCost = $this->toFloat($row['taken_cost'] ?? 0);
            $returnedQty = $this->toInt($row['returned_qty'] ?? 0);
            $returnedCost = $this->toFloat($row['returned_cost'] ?? 0);
            $outstandingQty = max(0, $takenQty - $returnedQty);
            $outstandingCost = max(0.0, $takenCost - $returnedCost);

            $item = [
                'user_name' => (string)$row['user_name'],
                'transaction_count' => $this->toInt($row['transaction_count'] ?? 0),
                'incoming_qty' => $this->toInt($row['incoming_qty'] ?? 0),
                'incoming_cost' => $this->toFloat($row['incoming_cost'] ?? 0),
                'taken_qty' => $takenQty,
                'taken_cost' => $takenCost,
                'returned_qty' => $returnedQty,
                'returned_cost' => $returnedCost,
                'not_returned_qty' => $outstandingQty,
                'not_returned_cost' => $this->toFloat($outstandingCost),
            ];

            $summary[] = $item;
            $totals['transaction_count'] += $item['transaction_count'];
            $totals['incoming_qty'] += $item['incoming_qty'];
            $totals['incoming_cost'] += $item['incoming_cost'];
            $totals['taken_qty'] += $item['taken_qty'];
            $totals['taken_cost'] += $item['taken_cost'];
            $totals['returned_qty'] += $item['returned_qty'];
            $totals['returned_cost'] += $item['returned_cost'];
            $totals['not_returned_qty'] += $item['not_returned_qty'];
            $totals['not_returned_cost'] += $item['not_returned_cost'];
        }

        $totals['user_count'] = count($summary);
        $totals['incoming_cost'] = $this->toFloat($totals['incoming_cost']);
        $totals['taken_cost'] = $this->toFloat($totals['taken_cost']);
        $totals['returned_cost'] = $this->toFloat($totals['returned_cost']);
        $totals['not_returned_cost'] = $this->toFloat($totals['not_returned_cost']);

        $movementSql = "SELECT
                {$userExpr} AS user_name,
                p.id AS part_id,
                p.fowler_part_number,
                p.supplier_part_number,
                p.name AS part_name,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) ELSE 0 END) AS taken_qty,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) * COALESCE(it.unit_price, 0) ELSE 0 END) AS taken_cost,
                SUM(CASE WHEN it.transaction_type = 'return' THEN it.quantity ELSE 0 END) AS returned_qty,
                SUM(CASE WHEN it.transaction_type = 'return' THEN it.quantity * COALESCE(it.unit_price, 0) ELSE 0 END) AS returned_cost
            FROM inventory_transactions it
            JOIN parts p ON p.id = it.part_id
            WHERE 1 = 1 {$dateSql}
            GROUP BY {$userExpr}, p.id, p.fowler_part_number, p.supplier_part_number, p.name
            HAVING taken_qty > 0 OR returned_qty > 0
            ORDER BY {$userExpr}, p.fowler_part_number";

        $movementRows = Database::query($movementSql, $params)->fetchAll();
        $movement = [];

        foreach ($movementRows as $row) {
            $takenQty = $this->toInt($row['taken_qty'] ?? 0);
            $takenCost = $this->toFloat($row['taken_cost'] ?? 0);
            $returnedQty = $this->toInt($row['returned_qty'] ?? 0);
            $returnedCost = $this->toFloat($row['returned_cost'] ?? 0);

            $movement[] = [
                'user_name' => (string)$row['user_name'],
                'part_id' => $this->toInt($row['part_id'] ?? 0),
                'fowler_part_number' => (string)($row['fowler_part_number'] ?? ''),
                'supplier_part_number' => (string)($row['supplier_part_number'] ?? ''),
                'part_name' => (string)($row['part_name'] ?? ''),
                'taken_qty' => $takenQty,
                'taken_cost' => $takenCost,
                'returned_qty' => $returnedQty,
                'returned_cost' => $returnedCost,
                'not_returned_qty' => max(0, $takenQty - $returnedQty),
                'not_returned_cost' => $this->toFloat(max(0.0, $takenCost - $returnedCost)),
            ];
        }

        return [
            'summary' => $summary,
            'movement' => $movement,
            'totals' => $totals,
        ];
    }

    private function buildSupplierReport(?string $fromTs, ?string $toTs): array
    {
        [$dateSql, $params] = $this->buildDateClause('it.created_at', $fromTs, $toTs);

        $summarySql = "SELECT
                s.id AS supplier_id,
                s.name AS supplier_name,
                COUNT(it.id) AS transaction_count,
                SUM(CASE WHEN it.transaction_type = 'incoming' THEN it.quantity ELSE 0 END) AS incoming_qty,
                SUM(CASE WHEN it.transaction_type = 'incoming' THEN it.quantity * COALESCE(it.unit_price, 0) ELSE 0 END) AS incoming_cost,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) ELSE 0 END) AS returned_qty,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) * COALESCE(it.unit_price, 0) ELSE 0 END) AS returned_cost
            FROM suppliers s
            LEFT JOIN inventory_transactions it
              ON it.reference_id = s.id
             AND it.reference_type IN ('vendor', 'supplier')
             {$dateSql}
            GROUP BY s.id, s.name
            ORDER BY s.name";

        $rows = Database::query($summarySql, $params)->fetchAll();
        $summary = [];
        $totals = [
            'supplier_count' => 0,
            'transaction_count' => 0,
            'incoming_qty' => 0,
            'incoming_cost' => 0.0,
            'returned_qty' => 0,
            'returned_cost' => 0.0,
            'net_qty' => 0,
            'net_cost' => 0.0,
        ];

        foreach ($rows as $row) {
            $incomingQty = $this->toInt($row['incoming_qty'] ?? 0);
            $incomingCost = $this->toFloat($row['incoming_cost'] ?? 0);
            $returnedQty = $this->toInt($row['returned_qty'] ?? 0);
            $returnedCost = $this->toFloat($row['returned_cost'] ?? 0);

            $item = [
                'supplier_id' => $this->toInt($row['supplier_id'] ?? 0),
                'supplier_name' => (string)($row['supplier_name'] ?? ''),
                'transaction_count' => $this->toInt($row['transaction_count'] ?? 0),
                'incoming_qty' => $incomingQty,
                'incoming_cost' => $incomingCost,
                'returned_qty' => $returnedQty,
                'returned_cost' => $returnedCost,
                'net_qty' => $incomingQty - $returnedQty,
                'net_cost' => $this->toFloat($incomingCost - $returnedCost),
            ];

            $summary[] = $item;
            $totals['transaction_count'] += $item['transaction_count'];
            $totals['incoming_qty'] += $item['incoming_qty'];
            $totals['incoming_cost'] += $item['incoming_cost'];
            $totals['returned_qty'] += $item['returned_qty'];
            $totals['returned_cost'] += $item['returned_cost'];
            $totals['net_qty'] += $item['net_qty'];
            $totals['net_cost'] += $item['net_cost'];
        }

        $totals['supplier_count'] = count($summary);
        $totals['incoming_cost'] = $this->toFloat($totals['incoming_cost']);
        $totals['returned_cost'] = $this->toFloat($totals['returned_cost']);
        $totals['net_cost'] = $this->toFloat($totals['net_cost']);

        $returnItemsSql = "SELECT
                s.name AS supplier_name,
                p.fowler_part_number,
                p.supplier_part_number,
                p.name AS part_name,
                ABS(it.quantity) AS quantity,
                COALESCE(it.unit_price, 0) AS unit_price,
                ABS(it.quantity) * COALESCE(it.unit_price, 0) AS total_cost,
                it.created_at,
                COALESCE(NULLIF(TRIM(it.created_by), ''), 'System') AS created_by
            FROM inventory_transactions it
            JOIN suppliers s
              ON s.id = it.reference_id
             AND it.reference_type IN ('vendor', 'supplier')
            JOIN parts p ON p.id = it.part_id
            WHERE it.transaction_type = 'outgoing' {$dateSql}
            ORDER BY it.created_at DESC, it.id DESC";

        $returnItemRows = Database::query($returnItemsSql, $params)->fetchAll();
        $returnedItems = [];

        foreach ($returnItemRows as $row) {
            $returnedItems[] = [
                'supplier_name' => (string)($row['supplier_name'] ?? ''),
                'fowler_part_number' => (string)($row['fowler_part_number'] ?? ''),
                'supplier_part_number' => (string)($row['supplier_part_number'] ?? ''),
                'part_name' => (string)($row['part_name'] ?? ''),
                'quantity' => $this->toInt($row['quantity'] ?? 0),
                'unit_price' => $this->toFloat($row['unit_price'] ?? 0),
                'total_cost' => $this->toFloat($row['total_cost'] ?? 0),
                'created_at' => (string)($row['created_at'] ?? ''),
                'created_by' => (string)($row['created_by'] ?? ''),
            ];
        }

        return [
            'summary' => $summary,
            'returned_items' => $returnedItems,
            'totals' => $totals,
        ];
    }

    private function buildTransactionReport(?string $fromTs, ?string $toTs): array
    {
        [$dateSql, $params] = $this->buildDateClause('it.created_at', $fromTs, $toTs);

        $matrixSql = "SELECT
                it.transaction_type,
                COALESCE(it.reference_type, 'unknown') AS reference_type,
                COUNT(*) AS transaction_count,
                SUM(it.quantity) AS signed_qty,
                SUM(ABS(it.quantity)) AS absolute_qty,
                SUM(ABS(it.quantity) * COALESCE(it.unit_price, 0)) AS total_cost
            FROM inventory_transactions it
            WHERE 1 = 1 {$dateSql}
            GROUP BY it.transaction_type, COALESCE(it.reference_type, 'unknown')
            ORDER BY it.transaction_type, reference_type";

        $rows = Database::query($matrixSql, $params)->fetchAll();
        $matrix = [];
        $typeTotals = [];
        $overall = [
            'transaction_count' => 0,
            'absolute_qty' => 0,
            'total_cost' => 0.0,
        ];

        foreach ($rows as $row) {
            $item = [
                'transaction_type' => (string)($row['transaction_type'] ?? ''),
                'reference_type' => (string)($row['reference_type'] ?? ''),
                'transaction_count' => $this->toInt($row['transaction_count'] ?? 0),
                'signed_qty' => $this->toInt($row['signed_qty'] ?? 0),
                'absolute_qty' => $this->toInt($row['absolute_qty'] ?? 0),
                'total_cost' => $this->toFloat($row['total_cost'] ?? 0),
            ];

            $matrix[] = $item;
            $overall['transaction_count'] += $item['transaction_count'];
            $overall['absolute_qty'] += $item['absolute_qty'];
            $overall['total_cost'] += $item['total_cost'];

            $type = $item['transaction_type'];
            if (!isset($typeTotals[$type])) {
                $typeTotals[$type] = [
                    'transaction_type' => $type,
                    'transaction_count' => 0,
                    'signed_qty' => 0,
                    'absolute_qty' => 0,
                    'total_cost' => 0.0,
                ];
            }

            $typeTotals[$type]['transaction_count'] += $item['transaction_count'];
            $typeTotals[$type]['signed_qty'] += $item['signed_qty'];
            $typeTotals[$type]['absolute_qty'] += $item['absolute_qty'];
            $typeTotals[$type]['total_cost'] += $item['total_cost'];
        }

        $overall['total_cost'] = $this->toFloat($overall['total_cost']);
        $typeTotals = array_values(array_map(function (array $item): array {
            $item['total_cost'] = $this->toFloat($item['total_cost']);
            return $item;
        }, $typeTotals));

        return [
            'matrix' => $matrix,
            'type_totals' => $typeTotals,
            'overall' => $overall,
        ];
    }

    private function buildPartReport(?string $fromTs, ?string $toTs): array
    {
        $inventorySql = "SELECT
                p.id AS part_id,
                p.fowler_part_number,
                p.supplier_part_number,
                p.name AS part_name,
                COALESCE(s.name, '') AS supplier_name,
                COALESCE(il.quantity, 0) AS stock,
                COALESCE(p.unit_price, 0) AS unit_price,
                (COALESCE(il.quantity, 0) * COALESCE(p.unit_price, 0)) AS inventory_cost
            FROM parts p
            LEFT JOIN suppliers s ON s.id = p.supplier_id
            LEFT JOIN inventory_levels il ON il.part_id = p.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.fowler_part_number";

        $inventoryRows = Database::query($inventorySql)->fetchAll();
        $inventory = [];
        $inventoryByPartId = [];
        $totals = [
            'part_count' => 0,
            'stock_units' => 0,
            'inventory_cost' => 0.0,
        ];

        foreach ($inventoryRows as $row) {
            $item = [
                'part_id' => $this->toInt($row['part_id'] ?? 0),
                'fowler_part_number' => (string)($row['fowler_part_number'] ?? ''),
                'supplier_part_number' => (string)($row['supplier_part_number'] ?? ''),
                'part_name' => (string)($row['part_name'] ?? ''),
                'supplier_name' => (string)($row['supplier_name'] ?? ''),
                'stock' => $this->toInt($row['stock'] ?? 0),
                'unit_price' => $this->toFloat($row['unit_price'] ?? 0),
                'inventory_cost' => $this->toFloat($row['inventory_cost'] ?? 0),
            ];

            $inventory[] = $item;
            $inventoryByPartId[$item['part_id']] = $item;
            $totals['stock_units'] += $item['stock'];
            $totals['inventory_cost'] += $item['inventory_cost'];
        }

        $totals['part_count'] = count($inventory);
        $totals['inventory_cost'] = $this->toFloat($totals['inventory_cost']);

        $openingSnapshot = $this->getInventorySnapshot($fromTs, false, true);
        $closingSnapshot = $this->getInventorySnapshot($toTs, true, false);
        $totals['opening_stock_units'] = $openingSnapshot['stock_units'];
        $totals['opening_inventory_cost'] = $openingSnapshot['inventory_cost'];
        $totals['closing_stock_units'] = $closingSnapshot['stock_units'];
        $totals['closing_inventory_cost'] = $closingSnapshot['inventory_cost'];
        $totals['inventory_cost_change'] = $this->toFloat(
            $closingSnapshot['inventory_cost'] - $openingSnapshot['inventory_cost']
        );

        [$dateSql, $params] = $this->buildDateClause('it.created_at', $fromTs, $toTs);
        $movementSql = "SELECT
                it.part_id,
                SUM(CASE WHEN it.transaction_type = 'incoming' THEN it.quantity ELSE 0 END) AS incoming_qty,
                SUM(CASE WHEN it.transaction_type = 'incoming' THEN it.quantity * COALESCE(it.unit_price, 0) ELSE 0 END) AS incoming_cost,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) ELSE 0 END) AS outgoing_qty,
                SUM(CASE WHEN it.transaction_type = 'outgoing' THEN ABS(it.quantity) * COALESCE(it.unit_price, 0) ELSE 0 END) AS outgoing_cost,
                SUM(CASE WHEN it.transaction_type = 'return' THEN it.quantity ELSE 0 END) AS return_qty,
                SUM(CASE WHEN it.transaction_type = 'return' THEN it.quantity * COALESCE(it.unit_price, 0) ELSE 0 END) AS return_cost,
                COUNT(*) AS movement_count
            FROM inventory_transactions it
            WHERE 1 = 1 {$dateSql}
            GROUP BY it.part_id";

        $movementRows = Database::query($movementSql, $params)->fetchAll();
        $movementMap = [];
        foreach ($movementRows as $row) {
            $partId = $this->toInt($row['part_id'] ?? 0);
            $movementMap[$partId] = [
                'incoming_qty' => $this->toInt($row['incoming_qty'] ?? 0),
                'incoming_cost' => $this->toFloat($row['incoming_cost'] ?? 0),
                'outgoing_qty' => $this->toInt($row['outgoing_qty'] ?? 0),
                'outgoing_cost' => $this->toFloat($row['outgoing_cost'] ?? 0),
                'return_qty' => $this->toInt($row['return_qty'] ?? 0),
                'return_cost' => $this->toFloat($row['return_cost'] ?? 0),
                'movement_count' => $this->toInt($row['movement_count'] ?? 0),
            ];
        }

        $movement = [];
        $movementTotals = [
            'incoming_qty' => 0,
            'incoming_cost' => 0.0,
            'outgoing_qty' => 0,
            'outgoing_cost' => 0.0,
            'return_qty' => 0,
            'return_cost' => 0.0,
            'movement_count' => 0,
            'net_qty' => 0,
            'net_cost' => 0.0,
        ];

        foreach ($inventory as $part) {
            $partId = $part['part_id'];
            $m = $movementMap[$partId] ?? [
                'incoming_qty' => 0,
                'incoming_cost' => 0.0,
                'outgoing_qty' => 0,
                'outgoing_cost' => 0.0,
                'return_qty' => 0,
                'return_cost' => 0.0,
                'movement_count' => 0,
            ];

            $netQty = $m['incoming_qty'] + $m['return_qty'] - $m['outgoing_qty'];
            $netCost = $m['incoming_cost'] + $m['return_cost'] - $m['outgoing_cost'];

            $item = [
                'part_id' => $partId,
                'fowler_part_number' => $part['fowler_part_number'],
                'supplier_part_number' => $part['supplier_part_number'],
                'part_name' => $part['part_name'],
                'incoming_qty' => $m['incoming_qty'],
                'incoming_cost' => $m['incoming_cost'],
                'outgoing_qty' => $m['outgoing_qty'],
                'outgoing_cost' => $m['outgoing_cost'],
                'return_qty' => $m['return_qty'],
                'return_cost' => $m['return_cost'],
                'movement_count' => $m['movement_count'],
                'net_qty' => $netQty,
                'net_cost' => $this->toFloat($netCost),
            ];

            $movement[] = $item;
            $movementTotals['incoming_qty'] += $item['incoming_qty'];
            $movementTotals['incoming_cost'] += $item['incoming_cost'];
            $movementTotals['outgoing_qty'] += $item['outgoing_qty'];
            $movementTotals['outgoing_cost'] += $item['outgoing_cost'];
            $movementTotals['return_qty'] += $item['return_qty'];
            $movementTotals['return_cost'] += $item['return_cost'];
            $movementTotals['movement_count'] += $item['movement_count'];
            $movementTotals['net_qty'] += $item['net_qty'];
            $movementTotals['net_cost'] += $item['net_cost'];
        }

        $movementTotals['incoming_cost'] = $this->toFloat($movementTotals['incoming_cost']);
        $movementTotals['outgoing_cost'] = $this->toFloat($movementTotals['outgoing_cost']);
        $movementTotals['return_cost'] = $this->toFloat($movementTotals['return_cost']);
        $movementTotals['net_cost'] = $this->toFloat($movementTotals['net_cost']);

        return [
            'inventory' => $inventory,
            'movement' => $movement,
            'totals' => [
                'inventory' => $totals,
                'movement' => $movementTotals,
            ],
        ];
    }

    private function toInt($value): int
    {
        return (int)round((float)($value ?? 0));
    }

    private function toFloat($value): float
    {
        return round((float)($value ?? 0), 2);
    }

    private function getInventorySnapshot(?string $timestamp, bool $inclusive, bool $emptyWhenNull): array
    {
        if ($timestamp === null && $emptyWhenNull) {
            return [
                'stock_units' => 0,
                'inventory_cost' => 0.0,
            ];
        }

        $sql = "SELECT
                    COALESCE(SUM(it.quantity), 0) AS stock_units,
                    COALESCE(SUM(it.quantity * COALESCE(it.unit_price, 0)), 0) AS inventory_cost
                FROM inventory_transactions it
                WHERE 1 = 1";
        $params = [];

        if ($timestamp !== null) {
            $operator = $inclusive ? '<=' : '<';
            $sql .= " AND it.created_at {$operator} ?";
            $params[] = $timestamp;
        }

        $row = Database::query($sql, $params)->fetch();

        return [
            'stock_units' => $this->toInt($row['stock_units'] ?? 0),
            'inventory_cost' => $this->toFloat($row['inventory_cost'] ?? 0),
        ];
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded report file is too large',
            UPLOAD_ERR_PARTIAL => 'Report upload was interrupted',
            UPLOAD_ERR_NO_FILE => 'Report file is required',
            default => 'Failed to upload report file',
        };
    }

    private function normalizeReportScope(string $scope): string
    {
        $normalized = strtolower(trim($scope));
        $allowed = ['all', 'users', 'suppliers', 'transactions', 'parts'];

        return in_array($normalized, $allowed, true) ? $normalized : 'all';
    }

    private function sanitizeFileBaseName(string $fileBaseName): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($fileBaseName));
        if ($safe === null) {
            return '';
        }

        $safe = trim($safe, '._-');
        if ($safe === '') {
            return '';
        }

        return substr($safe, 0, 80);
    }

    private function ensureArchiveDirectory(): string
    {
        $monthFolder = date('Y-m');
        $archiveDir = $this->archiveRootPath() . '/' . $monthFolder;

        if (!is_dir($archiveDir) && !mkdir($archiveDir, 0775, true) && !is_dir($archiveDir)) {
            Response::error('Unable to prepare report archive directory', 500);
        }

        return $archiveDir;
    }

    private function appendArchiveManifest(array $entry): void
    {
        $manifestDir = $this->archiveRootPath();
        if (!is_dir($manifestDir) && !mkdir($manifestDir, 0775, true) && !is_dir($manifestDir)) {
            Logger::warning('Failed to create report archive manifest directory', ['dir' => $manifestDir]);
            return;
        }

        $manifestPath = $this->archiveManifestPath();
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $result = @file_put_contents($manifestPath, $line, FILE_APPEND | LOCK_EX);

        if ($result === false) {
            Logger::warning('Failed to append report archive manifest', ['path' => $manifestPath]);
        }
    }

    private function archiveRootPath(): string
    {
        return dirname(__DIR__, 2) . '/storage/report-downloads';
    }

    private function archiveManifestPath(): string
    {
        return $this->archiveRootPath() . '/manifest.jsonl';
    }

    private function resolveArchivePath(string $requestedPath): ?string
    {
        $normalized = str_replace('\\', '/', trim($requestedPath));
        $normalized = ltrim($normalized, '/');

        if ($normalized === '' || str_contains($normalized, "\0")) {
            return null;
        }

        if (!str_starts_with($normalized, 'storage/report-downloads/')) {
            return null;
        }

        $appRoot = dirname(__DIR__, 2);
        $candidate = $appRoot . '/' . $normalized;
        $resolved = realpath($candidate);
        $root = realpath($this->archiveRootPath());

        if ($resolved === false || $root === false) {
            return null;
        }

        $resolvedNormalized = str_replace('\\', '/', $resolved);
        $rootNormalized = rtrim(str_replace('\\', '/', $root), '/');

        if ($resolvedNormalized !== $rootNormalized && strpos($resolvedNormalized, $rootNormalized . '/') !== 0) {
            return null;
        }

        return $resolved;
    }
}
