<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\LdapAuth;
use App\Core\Logger;
use App\Models\User;

class AuthController extends BaseController
{
    /**
     * Login with LDAP or local credentials
     * Priority: Local users (admin) -> LDAP users
     */
    public function login(): void
    {
        $request = new Request();
        
        $username = $request->get('username', '');
        $password = $request->get('password', '');
        
        if (empty($username) || empty($password)) {
            Response::error('Username and password are required', 400);
            return;
        }
        
        Logger::info('Login attempt', ['username' => $username]);
        
        $ldapConfig = require __DIR__ . '/../../config/ldap.php';
        $allowedGroups = $ldapConfig['allowed_groups'] ?? [];

        // Try local authentication first (for admin and fallback users)
        try {
            $localUser = User::findByUsername($username);
            
            if ($localUser && $localUser->verifyPassword($password)) {
                if (!empty($allowedGroups)) {
                    Logger::warning('Local authentication blocked by LDAP group restrictions', [
                        'username' => $username
                    ]);
                    Response::error('Access denied', 403);
                    return;
                }

                Logger::info('Local authentication successful', [
                    'username' => $username,
                    'role' => $localUser->role
                ]);
                
                // Update last login
                $localUser->updateLastLogin();
                
                // Start session
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $firstName = $this->extractFirstName($localUser->display_name, $localUser->username);

                // Store user info in session
                $_SESSION['user'] = [
                    'id' => $localUser->id,
                    'username' => $localUser->username,
                    'display_name' => $localUser->display_name,
                    'first_name' => $firstName,
                    'email' => $localUser->email ?? '',
                    'role' => $localUser->role,
                    'auth_type' => 'local',
                    'authenticated_at' => time(),
                ];
                
                // Set session expiration
                $_SESSION['expires_at'] = time() + $ldapConfig['session_lifetime'];
                
                Response::success([
                    'user' => [
                        'username' => $localUser->username,
                        'display_name' => $localUser->display_name,
                        'first_name' => $firstName,
                        'email' => $localUser->email ?? '',
                        'role' => $localUser->role,
                        'auth_type' => 'local'
                    ],
                    'message' => 'Login successful'
                ]);
                return;
            }
        } catch (\Exception $e) {
            Logger::error('Local authentication error', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
        }
        
        // If local auth fails, try LDAP (if available)
        if (LdapAuth::isAvailable()) {
            try {
                $ldap = new LdapAuth();
                $user = $ldap->authenticate($username, $password);
                
                if ($user) {
                    Logger::info('LDAP authentication successful', [
                        'username' => $user['username']
                    ]);
                    
                    // Start session
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    // Store user info in session (LDAP users have 'user' role)
                    $firstName = $user['first_name'] ?? $this->extractFirstName(
                        $user['display_name'] ?? $user['username'],
                        $user['username']
                    );
                    $role = $user['role'] ?? 'user';
                    $dbUser = ['id' => null, 'role' => $role];

                    // Authentication should not fail if user profile sync cannot reach DB.
                    try {
                        $dbUser = User::upsertLdapUser([
                            'username' => $user['username'],
                            'display_name' => $user['display_name'],
                            'email' => $user['email'],
                            'role' => $role
                        ]);
                        $role = $dbUser['role'] ?? $role;
                    } catch (\Throwable $syncError) {
                        Logger::warning('LDAP user sync failed; continuing with session-only login', [
                            'username' => $user['username'],
                            'error' => $syncError->getMessage()
                        ]);
                    }

                    $_SESSION['user'] = [
                        'id' => $dbUser['id'] ?? null,
                        'username' => $user['username'],
                        'display_name' => $user['display_name'],
                        'first_name' => $firstName,
                        'email' => $user['email'],
                        'role' => $role,
                        'groups' => $user['groups'],
                        'auth_type' => 'ldap',
                        'authenticated_at' => time(),
                    ];
                    
                    // Set session expiration
                    $_SESSION['expires_at'] = time() + $ldapConfig['session_lifetime'];
                    
                    Response::success([
                    'user' => [
                        'username' => $user['username'],
                        'display_name' => $user['display_name'],
                        'first_name' => $firstName,
                        'email' => $user['email'],
                        'role' => $role,
                        'auth_type' => 'ldap'
                    ],
                        'message' => 'Login successful'
                    ]);
                    return;
                }
            } catch (\Exception $e) {
                Logger::warning('LDAP authentication failed', [
                    'username' => $username,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Logger::warning('LDAP extension not available, using local auth only');
        }
        
        // Both authentication methods failed
        Logger::warning('Login failed - invalid credentials', ['username' => $username]);
        Response::error('Invalid credentials', 401);
    }
    
    /**
     * Logout
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $username = $_SESSION['user']['username'] ?? 'unknown';
        
        // Destroy session
        $_SESSION = [];
        session_destroy();
        
        Logger::info('User logged out', ['username' => $username]);
        
        Response::success(['message' => 'Logout successful']);
    }
    
    /**
     * Get current user info
     */
    public function me(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user'])) {
            Response::error('Not authenticated', 401);
            return;
        }
        
        // Check if session has expired
        if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
            $_SESSION = [];
            session_destroy();
            Response::error('Session expired', 401);
            return;
        }
        
        Response::success([
            'user' => $_SESSION['user']
        ]);
    }
    
    /**
     * Check authentication status
     */
    public function check(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $authenticated = isset($_SESSION['user']);
        
        if ($authenticated && isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
            $_SESSION = [];
            session_destroy();
            $authenticated = false;
        }
        
        Response::success([
            'authenticated' => $authenticated
        ]);
    }

    private function extractFirstName(string $displayName, string $fallback): string
    {
        $displayName = trim($displayName);
        if ($displayName === '') {
            return $fallback;
        }

        $parts = preg_split('/\s+/', $displayName);
        return $parts[0] ?? $fallback;
    }
}
