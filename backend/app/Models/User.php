<?php

namespace App\Models;

use App\Core\BaseModel;
use App\Core\Database;

class User extends BaseModel
{
    protected string $table = 'users';
    private static bool $schemaChecked = false;
    
    protected array $fillable = [
        'username',
        'password_hash',
        'display_name',
        'email',
        'role',
        'auth_source',
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
    public $auth_source;
    public $is_active;
    public $last_login;
    public $created_at;
    public $updated_at;

    public function __construct()
    {
        self::ensureSchema();
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($password)
    {
        $authSource = self::normalizeAuthSource($this->auth_source ?? 'local');
        $passwordHash = trim((string)($this->password_hash ?? ''));

        if ($authSource === 'ldap' || $passwordHash === '') {
            return false;
        }

        return password_verify($password, $passwordHash);
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
        self::ensureSchema();

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
        self::ensureSchema();

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
                    auth_source = 'ldap',
                    password_hash = NULL,
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

        Database::query("
            INSERT INTO users (username, password_hash, display_name, email, role, auth_source, is_active, last_login)
            VALUES (?, NULL, ?, ?, ?, 'ldap', 1, NOW())
        ", [
            $username,
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
        self::ensureSchema();

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
        self::ensureSchema();

        $authSource = self::normalizeAuthSource($this->auth_source ?? 'local');
        $passwordHash = $authSource === 'ldap' ? null : $this->password_hash;

        if ($this->id) {
            // Update existing user
            Database::query("
                UPDATE users 
                SET password_hash = ?,
                    display_name = ?,
                    email = ?,
                    role = ?,
                    auth_source = ?,
                    is_active = ?
                WHERE id = ?
            ", [
                $passwordHash,
                $this->display_name,
                $this->email,
                $this->role,
                $authSource,
                $this->is_active,
                $this->id
            ]);
        } else {
            // Insert new user
            Database::query("
                INSERT INTO users (username, password_hash, display_name, email, role, auth_source, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [
                $this->username,
                $passwordHash,
                $this->display_name,
                $this->email,
                $this->role,
                $authSource,
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
            'auth_type' => self::normalizeAuthSource($this->auth_source ?? 'local')
        ];
    }

    private static function normalizeAuthSource(?string $authSource): string
    {
        return strtolower(trim((string)$authSource)) === 'ldap' ? 'ldap' : 'local';
    }

    private static function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        try {
            $authSourceColumn = Database::query("
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'users'
                  AND column_name = 'auth_source'
                LIMIT 1
            ")->fetch();

            if (!$authSourceColumn) {
                Database::query("
                    ALTER TABLE users
                    ADD COLUMN auth_source VARCHAR(20) NOT NULL DEFAULT 'local' AFTER role
                ");
            }

            $passwordHashColumn = Database::query("
                SELECT is_nullable
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'users'
                  AND column_name = 'password_hash'
                LIMIT 1
            ")->fetch(\PDO::FETCH_ASSOC);

            if (($passwordHashColumn['is_nullable'] ?? '') !== 'YES') {
                Database::query("
                    ALTER TABLE users
                    MODIFY COLUMN password_hash VARCHAR(255) NULL
                ");
            }

            Database::query("
                UPDATE users
                SET auth_source = 'local'
                WHERE auth_source IS NULL OR TRIM(auth_source) = ''
            ");

            $auditLogsTable = Database::query("
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'audit_logs'
                LIMIT 1
            ")->fetch();

            if ($auditLogsTable) {
                Database::query("
                    UPDATE users u
                    INNER JOIN (
                        SELECT DISTINCT username
                        FROM audit_logs
                        WHERE auth_type = 'ldap'
                          AND username IS NOT NULL
                          AND TRIM(username) <> ''
                    ) ldap_users
                        ON ldap_users.username = u.username
                    SET u.auth_source = 'ldap',
                        u.password_hash = NULL
                ");
            }

            Database::query("
                UPDATE users
                SET password_hash = NULL
                WHERE auth_source = 'ldap'
            ");

            self::$schemaChecked = true;
        } catch (\Throwable $e) {
            error_log('User schema check failed: ' . $e->getMessage());
        }
    }
}
