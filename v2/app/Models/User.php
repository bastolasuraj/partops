<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * User Model
 * This file should be moved to: app/Models/User.php
 */
class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'username',
        'email',
        'auth_source',
        'password_hash',
        'role',
        'is_active'
    ];

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy('username', $username);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->execute(
            "UPDATE users SET last_login_at = NOW() WHERE id = :id",
            ['id' => $userId]
        );
    }

    /**
     * Authenticate user (local)
     */
    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return null;
        }
        
        if (!$user['is_active']) {
            return null;
        }
        
        if ($user['auth_source'] !== 'local') {
            return null; // LDAP users should use LDAP auth
        }
        
        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return null;
        }
        
        $this->updateLastLogin($user['id']);
        
        // Remove sensitive data
        unset($user['password_hash']);
        
        return $user;
    }

    /**
     * Create LDAP user (auto-created on first LDAP login)
     */
    public function createLdapUser(string $username, string $email): int
    {
        return $this->create([
            'username' => $username,
            'email' => $email,
            'auth_source' => 'ldap',
            'role' => 'user', // LDAP users default to 'user' role
            'is_active' => true
        ]);
    }

    /**
     * Get all admins
     */
    public function getAdmins(): array
    {
        return $this->all(['role' => 'admin', 'is_active' => 1], 'username');
    }

    /**
     * Get all active users
     */
    public function getActive(): array
    {
        return $this->all(['is_active' => 1], 'username');
    }
}
