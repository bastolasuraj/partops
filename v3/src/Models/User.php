<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

class User extends BaseModel
{
    protected static string $table = 'users';

    public static function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        return Database::queryOne($sql, ['username' => $username]);
    }

    public static function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        return Database::queryOne($sql, ['email' => $email]);
    }

    public static function updateLastLogin(int $id): bool
    {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id";
        return Database::execute($sql, ['id' => $id]) > 0;
    }

    public static function getActive(): array
    {
        $sql = "SELECT id, username, email, role, auth_source, last_login_at, created_at 
                FROM users WHERE is_active = true ORDER BY username";
        return Database::query($sql);
    }

    public static function create(array $data): ?int
    {
        self::enforceAuthSourceRoleAlignment($data);
        return parent::create($data);
    }

    public static function update(int $id, array $data): bool
    {
        if (isset($data['role']) || isset($data['auth_source'])) {
            $existing = self::find($id);
            if ($existing) {
                $data['auth_source'] = $data['auth_source'] ?? $existing['auth_source'];
                $data['role'] = $data['role'] ?? $existing['role'];
                self::enforceAuthSourceRoleAlignment($data);
            }
        }
        return parent::update($id, $data);
    }

    private static function enforceAuthSourceRoleAlignment(array &$data): void
    {
        $authSource = $data['auth_source'] ?? 'local';
        $role = $data['role'] ?? 'user';
        
        if ($authSource === 'ldap' && $role === 'admin') {
            throw new \InvalidArgumentException('LDAP users cannot be administrators. Admins must use local authentication.');
        }
        
        if ($authSource === 'local' && $role === 'user') {
            throw new \InvalidArgumentException('Local accounts are for administrators only. Regular users must use LDAP.');
        }
    }

    public static function createLdapUser(string $username, string $email): ?int
    {
        return self::create([
            'username' => $username,
            'email' => $email,
            'auth_source' => 'ldap',
            'role' => 'user',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
