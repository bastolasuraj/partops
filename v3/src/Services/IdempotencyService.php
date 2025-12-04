<?php
declare(strict_types=1);

namespace PartOps\Services;

class IdempotencyService
{
    public static function generate(): string
    {
        return 'idem-' . date('Ymd') . '-' . bin2hex(random_bytes(8));
    }

    public static function check(string $key): bool
    {
        self::cleanupExpired();
        
        $sql = "SELECT id FROM idempotency_keys WHERE key_value = :key LIMIT 1";
        $result = Database::queryOne($sql, ['key' => $key]);
        
        return $result === null;
    }

    public static function markUsed(string $key, ?int $userId = null): void
    {
        $sql = "INSERT INTO idempotency_keys (key_value, user_id, created_at, expires_at) 
                VALUES (:key, :user_id, NOW(), NOW() + INTERVAL 1 HOUR)
                ON DUPLICATE KEY UPDATE key_value = key_value";
        
        Database::execute($sql, [
            'key' => $key,
            'user_id' => $userId ?? Auth::id()
        ]);
    }

    public static function checkAndMark(string $key): bool
    {
        Database::beginTransaction();
        try {
            $sql = "SELECT id FROM idempotency_keys WHERE key_value = :key FOR UPDATE";
            $result = Database::queryOne($sql, ['key' => $key]);
            
            if ($result !== null) {
                Database::rollback();
                return false;
            }

            self::markUsed($key);
            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollback();
            return false;
        }
    }

    private static function cleanupExpired(): void
    {
        $sql = "DELETE FROM idempotency_keys WHERE expires_at < NOW()";
        Database::execute($sql);
    }
}
