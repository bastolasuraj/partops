<?php
declare(strict_types=1);

namespace PartOps\Services;

class AuditLog
{
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $diff = null,
        ?string $correlationId = null
    ): void {
        $userId = Auth::id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $correlationId = $correlationId ?? self::generateCorrelationId();

        $sql = "INSERT INTO audit_log (user_id, action, entity_type, entity_id, diff, correlation_id, ip, created_at)
                VALUES (:user_id, :action, :entity_type, :entity_id, :diff, :correlation_id, :ip, NOW())";

        Database::execute($sql, [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'diff' => $diff ? json_encode($diff) : null,
            'correlation_id' => $correlationId,
            'ip' => $ip
        ]);
    }

    public static function generateCorrelationId(): string
    {
        return uniqid('audit-', true);
    }

    public static function getByEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        $sql = "SELECT al.*, u.username 
                FROM audit_log al 
                LEFT JOIN users u ON al.user_id = u.id 
                WHERE al.entity_type = :entity_type AND al.entity_id = :entity_id 
                ORDER BY al.created_at DESC 
                LIMIT :limit";

        return Database::query($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'limit' => $limit
        ]);
    }

    public static function getRecent(int $limit = 100): array
    {
        $sql = "SELECT al.*, u.username 
                FROM audit_log al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC 
                LIMIT :limit";

        return Database::query($sql, ['limit' => $limit]);
    }
}
