<?php
/**
 * User Model
 * 
 * Handle user data operations
 */

declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET last_login_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$userId]);
    }

    /**
     * Create new user
     */
    public function createUser(array $data): int
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $this->create($data);
    }
}
