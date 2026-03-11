<?php

namespace App\Models;

use App\Core\Database;

class AuditLog
{
    private static bool $tableEnsured = false;

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
    }

    public static function recentByCategory(string $category, int $limit = 100): array
    {
        self::ensureTableExists();

        $safeLimit = max(1, min($limit, 500));

        $rows = Database::query("
            SELECT *
            FROM audit_logs
            WHERE category = ?
            ORDER BY created_at DESC, id DESC
            LIMIT {$safeLimit}
        ", [$category])->fetchAll();

        return array_map([self::class, 'transformRow'], $rows ?: []);
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
}
