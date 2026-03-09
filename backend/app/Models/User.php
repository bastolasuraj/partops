<?php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class User extends BaseModel
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'username',
        'password_hash',
        'display_name',
        'email',
        'role',
        'is_active',
        'last_login'
    ];
    
    protected array $hidden = [
        'password_hash'
    ];
    
    // Properties for object-style access
    public $id;
    public $username;
    public $password_hash;
    public $display_name;
    public $email;
    public $role;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;
    
    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
    
    /**
     * Find user by username
     */
    public static function findByUsername($username)
    {
        $stmt = Database::query("
            SELECT * FROM users 
            WHERE username = ? AND is_active = 1
            LIMIT 1
        ", [$username]);
        
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($data) {
            $user = new self();
            foreach ($data as $key => $value) {
                $user->$key = $value;
            }
            return $user;
        }
        
        return null;
    }

    /**
     * Upsert LDAP user details into local users table (no password sync).
     * Preserves admin role if already set locally.
     */
    public static function upsertLdapUser(array $ldapUser): array
    {
        $username = $ldapUser['username'] ?? '';
        if ($username === '') {
            return ['id' => null, 'role' => $ldapUser['role'] ?? 'user'];
        }

        $displayName = $ldapUser['display_name'] ?? $username;
        $email = $ldapUser['email'] ?? '';
        $role = $ldapUser['role'] ?? 'user';

        $existing = Database::query("
            SELECT id, role 
            FROM users 
            WHERE username = ?
            LIMIT 1
        ", [$username])->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            $finalRole = ($existing['role'] ?? '') === 'admin' ? 'admin' : $role;
            Database::query("
                UPDATE users 
                SET display_name = ?,
                    email = ?,
                    role = ?,
                    is_active = 1,
                    last_login = NOW()
                WHERE id = ?
            ", [
                $displayName,
                $email,
                $finalRole,
                $existing['id']
            ]);

            return [
                'id' => (int)$existing['id'],
                'role' => $finalRole
            ];
        }

        $passwordHash = self::hashPassword(bin2hex(random_bytes(16)));
        Database::query("
            INSERT INTO users (username, password_hash, display_name, email, role, is_active, last_login)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ", [
            $username,
            $passwordHash,
            $displayName,
            $email,
            $role
        ]);

        return [
            'id' => (int)Database::lastInsertId(),
            'role' => $role
        ];
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin()
    {
        Database::query("
            UPDATE users 
            SET last_login = NOW() 
            WHERE id = ?
        ", [$this->id]);
        
        return true;
    }
    
    /**
     * Save user (for updating password, etc.)
     */
    public function save()
    {
        if ($this->id) {
            // Update existing user
            Database::query("
                UPDATE users 
                SET password_hash = ?,
                    display_name = ?,
                    email = ?,
                    role = ?,
                    is_active = ?
                WHERE id = ?
            ", [
                $this->password_hash,
                $this->display_name,
                $this->email,
                $this->role,
                $this->is_active,
                $this->id
            ]);
        } else {
            // Insert new user
            Database::query("
                INSERT INTO users (username, password_hash, display_name, email, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $this->username,
                $this->password_hash,
                $this->display_name,
                $this->email,
                $this->role,
                $this->is_active ?? 1
            ]);
            
            $this->id = Database::lastInsertId();
        }
        
        return true;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * Get user data for session (without sensitive info)
     */
    public function toSessionData()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'email' => $this->email,
            'role' => $this->role,
            'auth_type' => 'local'
        ];
    }
}
