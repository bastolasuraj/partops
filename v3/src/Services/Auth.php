<?php
declare(strict_types=1);

namespace PartOps\Services;

use PartOps\Models\User;

class Auth
{
    private const USER_KEY = 'authenticated_user';

    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);
        
        if (!$user) {
            return false;
        }

        if (!$user['is_active']) {
            return false;
        }

        if ($user['auth_source'] === 'ldap') {
            if (!self::ldapAuth($username, $password)) {
                return false;
            }
        } else {
            if (!password_verify($password, $user['password_hash'] ?? '')) {
                return false;
            }
        }

        User::updateLastLogin($user['id']);
        Session::regenerate();
        Session::set(self::USER_KEY, [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'auth_source' => $user['auth_source']
        ]);

        return true;
    }

    private static function ldapAuth(string $username, string $password): bool
    {
        if (!($_ENV['LDAP_ENABLED'] ?? false)) {
            return false;
        }

        $ldapHost = $_ENV['LDAP_HOST'] ?? '';
        $ldapPort = (int)($_ENV['LDAP_PORT'] ?? 389);
        $baseDn = $_ENV['LDAP_BASE_DN'] ?? '';

        if (empty($ldapHost) || empty($baseDn)) {
            return false;
        }

        $ldap = @ldap_connect($ldapHost, $ldapPort);
        if (!$ldap) {
            return false;
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        $userDn = "uid=$username,$baseDn";
        $bind = @ldap_bind($ldap, $userDn, $password);
        ldap_close($ldap);

        return $bind;
    }

    public static function check(): bool
    {
        return Session::has(self::USER_KEY);
    }

    public static function user(): ?array
    {
        return Session::get(self::USER_KEY);
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function logout(): void
    {
        Session::remove(self::USER_KEY);
        Session::regenerate();
    }

    public static function createLocalAdmin(string $username, string $email, string $password): ?int
    {
        return User::create([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'auth_source' => 'local',
            'role' => 'admin',
            'is_active' => true
        ]);
    }
}
