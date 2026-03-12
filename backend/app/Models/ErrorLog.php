<?php

namespace App\Models;

use App\Core\Database;

class ErrorLog
{
    private static bool $tableEnsured = false;
    private const RECENT_LIMIT = 25;

    public static function ensureTableExists(): void
    {
        if (self::$tableEnsured) {
            return;
        }

        Database::query("
            CREATE TABLE IF NOT EXISTS error_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                source VARCHAR(50) NOT NULL DEFAULT 'backend',
                error_kind VARCHAR(100) NOT NULL DEFAULT 'application_error',
                message TEXT NOT NULL,
                stack_trace LONGTEXT NULL,
                status_code INT NULL,
                user_id INT NULL,
                username VARCHAR(100) NULL,
                display_name VARCHAR(255) NULL,
                user_role VARCHAR(50) NULL,
                http_method VARCHAR(20) NULL,
                route_path VARCHAR(255) NULL,
                ip_address VARCHAR(64) NULL,
                user_agent VARCHAR(500) NULL,
                metadata LONGTEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created (created_at),
                INDEX idx_source_created (source, created_at),
                INDEX idx_status_created (status_code, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        self::$tableEnsured = true;
    }

    public static function create(array $attributes): void
    {
        self::ensureTableExists();

        Database::query("
            INSERT INTO error_logs (
                source,
                error_kind,
                message,
                stack_trace,
                status_code,
                user_id,
                username,
                display_name,
                user_role,
                http_method,
                route_path,
                ip_address,
                user_agent,
                metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            self::trimString($attributes['source'] ?? 'backend', 50) ?? 'backend',
            self::trimString($attributes['error_kind'] ?? 'application_error', 100) ?? 'application_error',
            self::trimString($attributes['message'] ?? 'Application error recorded', 65535) ?? 'Application error recorded',
            self::trimString($attributes['stack_trace'] ?? null, 65535),
            isset($attributes['status_code']) && $attributes['status_code'] !== null ? (int)$attributes['status_code'] : null,
            isset($attributes['user_id']) && $attributes['user_id'] !== null ? (int)$attributes['user_id'] : null,
            self::trimString($attributes['username'] ?? null, 100),
            self::trimString($attributes['display_name'] ?? null, 255),
            self::trimString($attributes['user_role'] ?? null, 50),
            self::trimString($attributes['http_method'] ?? null, 20),
            self::trimString($attributes['route_path'] ?? null, 255),
            self::trimString($attributes['ip_address'] ?? null, 64),
            self::trimString($attributes['user_agent'] ?? null, 500),
            self::encodeJson($attributes['metadata'] ?? null),
        ]);

        self::trimToRecentLimit();
    }

    public static function getRecent(int $limit = self::RECENT_LIMIT): array
    {
        self::ensureTableExists();

        $safeLimit = max(1, min($limit, self::RECENT_LIMIT));

        $rows = Database::query("
            SELECT *
            FROM error_logs
            ORDER BY created_at DESC, id DESC
            LIMIT {$safeLimit}
        ")->fetchAll();

        return array_map([self::class, 'transformRow'], $rows ?: []);
    }

    public static function countCurrent(): int
    {
        self::ensureTableExists();

        return (int)(Database::query("
            SELECT COUNT(*) AS total
            FROM error_logs
        ")->fetch()['total'] ?? 0);
    }

    public static function getRecentLimit(): int
    {
        return self::RECENT_LIMIT;
    }

    private static function trimToRecentLimit(): void
    {
        $total = self::countCurrent();
        if ($total <= self::RECENT_LIMIT) {
            return;
        }

        Database::query("
            DELETE FROM error_logs
            WHERE id NOT IN (
                SELECT id
                FROM (
                    SELECT id
                    FROM error_logs
                    ORDER BY created_at DESC, id DESC
                    LIMIT " . self::RECENT_LIMIT . "
                ) AS recent_errors
            )
        ");
    }

    private static function transformRow(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int)$row['id'] : null;
        $row['status_code'] = isset($row['status_code']) && $row['status_code'] !== null ? (int)$row['status_code'] : null;
        $row['user_id'] = isset($row['user_id']) && $row['user_id'] !== null ? (int)$row['user_id'] : null;
        $row['metadata'] = self::decodeJson($row['metadata'] ?? null);
        $row['outcome'] = 'failure';
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

    private static function trimString($value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string)$value);
        if ($string === '') {
            return null;
        }

        if (strlen($string) <= $maxLength) {
            return $string;
        }

        if ($maxLength <= 3) {
            return substr($string, 0, $maxLength);
        }

        return substr($string, 0, $maxLength - 3) . '...';
    }
}
