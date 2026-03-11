<?php

namespace App\Models;

use App\Core\Database;

class AuditLog
{
    private static bool $tableEnsured = false;
    private const DEFAULT_ACTIVE_LOG_LIMIT = 20;
    private const DEFAULT_PAGE_SIZE = 25;
    private const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];
    private const ARCHIVE_FILE = 'old_logs.json';

    public static function ensureTableExists(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        Database::query("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                category ENUM('user', 'action') NOT NULL,
                action VARCHAR(100) NOT NULL,
                description VARCHAR(255) NOT NULL,
                outcome ENUM('success', 'failure') NOT NULL DEFAULT 'success',
                user_id INT NULL,
                username VARCHAR(100) NULL,
                display_name VARCHAR(255) NULL,
                user_role VARCHAR(50) NULL,
                auth_type VARCHAR(30) NULL,
                http_method VARCHAR(10) NULL,
                route_path VARCHAR(255) NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(500) NULL,
                origin VARCHAR(255) NULL,
                resource_type VARCHAR(100) NULL,
                resource_id VARCHAR(100) NULL,
                request_payload LONGTEXT NULL,
                metadata LONGTEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_category_created (category, created_at),
                INDEX idx_username_created (username, created_at),
                INDEX idx_route_created (route_path, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        self::$tableEnsured = true;
    }

    public static function create(array $attributes): void
    {
        self::ensureTableExists();

        Database::query("
            INSERT INTO audit_logs (
                category,
                action,
                description,
                outcome,
                user_id,
                username,
                display_name,
                user_role,
                auth_type,
                http_method,
                route_path,
                ip_address,
                user_agent,
                origin,
                resource_type,
                resource_id,
                request_payload,
                metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $attributes['category'] ?? 'action',
            $attributes['action'] ?? 'unknown',
            $attributes['description'] ?? 'Activity recorded',
            $attributes['outcome'] ?? 'success',
            $attributes['user_id'] ?? null,
            $attributes['username'] ?? null,
            $attributes['display_name'] ?? null,
            $attributes['user_role'] ?? null,
            $attributes['auth_type'] ?? null,
            $attributes['http_method'] ?? null,
            $attributes['route_path'] ?? null,
            $attributes['ip_address'] ?? null,
            $attributes['user_agent'] ?? null,
            $attributes['origin'] ?? null,
            $attributes['resource_type'] ?? null,
            $attributes['resource_id'] ?? null,
            self::encodeJson($attributes['request_payload'] ?? null),
            self::encodeJson($attributes['metadata'] ?? null),
        ]);

        self::archiveOverflowLogs();
    }

    public static function paginateByCategory(string $category, int $page = 1, int $perPage = 25): array
    {
        self::ensureTableExists();

        $safePage = max(1, $page);
        $safePerPage = self::sanitizePageSize($perPage);
        $total = self::countByCategory($category);
        $totalPages = max(1, (int)ceil($total / $safePerPage));

        if ($safePage > $totalPages) {
            $safePage = $totalPages;
        }

        $offset = ($safePage - 1) * $safePerPage;

        $rows = Database::query("
            SELECT *
            FROM audit_logs
            WHERE category = ?
            ORDER BY created_at DESC, id DESC
            LIMIT {$safePerPage} OFFSET {$offset}
        ", [$category])->fetchAll();

        return [
            'data' => array_map([self::class, 'transformRow'], $rows ?: []),
            'pagination' => [
                'page' => $safePage,
                'per_page' => $safePerPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'from' => $total === 0 ? 0 : $offset + 1,
                'to' => $total === 0 ? 0 : min($offset + $safePerPage, $total),
            ],
        ];
    }

    public static function getActiveLogLimit(): int
    {
        return self::getConfiguredPositiveInt('audit_logs_retention_limit', self::DEFAULT_ACTIVE_LOG_LIMIT, 1, 100000);
    }

    public static function getDefaultPageSize(): int
    {
        $configured = self::getConfiguredPositiveInt('audit_logs_page_size', self::DEFAULT_PAGE_SIZE, 10, 100);
        return self::sanitizePageSize($configured);
    }

    public static function getArchiveFileName(): string
    {
        return self::ARCHIVE_FILE;
    }

    public static function getPageSizeOptions(): array
    {
        return self::PAGE_SIZE_OPTIONS;
    }

    public static function enforceRetention(): void
    {
        self::ensureTableExists();
        self::archiveOverflowLogs();
    }

    private static function transformRow(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int)$row['id'] : null;
        $row['user_id'] = isset($row['user_id']) && $row['user_id'] !== null ? (int)$row['user_id'] : null;
        $row['request_payload'] = self::decodeJson($row['request_payload'] ?? null);
        $row['metadata'] = self::decodeJson($row['metadata'] ?? null);
        return $row;
    }

    private static function encodeJson($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function decodeJson(?string $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private static function countByCategory(string $category): int
    {
        return (int)(Database::query("
            SELECT COUNT(*) AS total
            FROM audit_logs
            WHERE category = ?
        ", [$category])->fetch()['total'] ?? 0);
    }

    private static function archiveOverflowLogs(): void
    {
        try {
            $totalLogs = (int)(Database::query("
                SELECT COUNT(*) AS total
                FROM audit_logs
            ")->fetch()['total'] ?? 0);

            $overflow = $totalLogs - self::getActiveLogLimit();
            if ($overflow <= 0) {
                return;
            }

            $rows = Database::query("
                SELECT *
                FROM audit_logs
                ORDER BY created_at ASC, id ASC
                LIMIT {$overflow}
            ")->fetchAll();

            if (!$rows) {
                return;
            }

            self::appendArchivedRows($rows);

            $ids = array_values(array_filter(array_map(
                static fn (array $row): int => (int)($row['id'] ?? 0),
                $rows
            )));

            if (!$ids) {
                return;
            }

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            Database::query("
                DELETE FROM audit_logs
                WHERE id IN ({$placeholders})
            ", $ids);
        } catch (\Throwable $e) {
            error_log('Audit log archival failed: ' . $e->getMessage());
        }
    }

    private static function appendArchivedRows(array $rows): void
    {
        $archivePath = self::getArchivePath();
        $archiveDir = dirname($archivePath);

        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0777, true);
        }

        $existing = [];
        if (is_file($archivePath)) {
            $decoded = json_decode((string)file_get_contents($archivePath), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $archivedAt = date('c');
        foreach ($rows as $row) {
            $entry = self::transformRow($row);
            $entry['archived_at'] = $archivedAt;
            $existing[] = $entry;
        }

        $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode archived audit logs');
        }

        $bytesWritten = file_put_contents($archivePath, $json, LOCK_EX);
        if ($bytesWritten === false) {
            throw new \RuntimeException('Failed to write archived audit logs');
        }
    }

    private static function getArchivePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs/' . self::ARCHIVE_FILE;
    }

    private static function sanitizePageSize(int $perPage): int
    {
        return in_array($perPage, self::PAGE_SIZE_OPTIONS, true)
            ? $perPage
            : self::DEFAULT_PAGE_SIZE;
    }

    private static function getConfiguredPositiveInt(string $key, int $default, int $min, int $max): int
    {
        try {
            $settings = new Setting();
            $value = $settings->get($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }

        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min((int)$value, $max));
    }
}
