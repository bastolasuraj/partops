<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * AuditLog Model
 * This file should be moved to: app/Models/AuditLog.php
 */
class AuditLog extends Model
{
    protected string $table = 'audit_log';
    protected array $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'correlation_id'
    ];
    protected bool $timestamps = false;

    /**
     * Log an action
     */
    public function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): int {
        $userId = $_SESSION['user']['id'] ?? null;
        $correlationId = $_SESSION['correlation_id'] ?? bin2hex(random_bytes(8));
        
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'correlation_id' => $correlationId
        ]);
    }

    /**
     * Get recent logs
     */
    public function getRecent(int $limit = 100): array
    {
        $sql = "
            SELECT al.*, u.username
            FROM audit_log al
            LEFT JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get logs for entity
     */
    public function getForEntity(string $entityType, int $entityId): array
    {
        $sql = "
            SELECT al.*, u.username
            FROM audit_log al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.entity_type = :entity_type AND al.entity_id = :entity_id
            ORDER BY al.created_at DESC
        ";
        
        return $this->query($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ]);
    }

    /**
     * Get logs by user
     */
    public function getByUser(int $userId, int $limit = 100): array
    {
        $sql = "
            SELECT * FROM audit_log
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get logs by action
     */
    public function getByAction(string $action, int $limit = 100): array
    {
        $sql = "
            SELECT al.*, u.username
            FROM audit_log al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.action = :action
            ORDER BY al.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Search logs
     */
    public function search(array $filters, int $limit = 100): array
    {
        $sql = "
            SELECT al.*, u.username
            FROM audit_log al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = :action";
            $params['action'] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $sql .= " AND al.entity_type = :entity_type";
            $params['entity_type'] = $filters['entity_type'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND al.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND al.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT {$limit}";
        
        return $this->query($sql, $params);
    }
}
