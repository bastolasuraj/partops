<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\AuditLog;

/**
 * Auth Controller - Login/Logout
 * This file should be moved to: app/Controllers/AuthController.php
 */
class AuthController extends Controller
{
    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/');
        }

        $this->view('auth/login', [
            'title' => 'Login'
        ]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->back();
            return;
        }

        $username = trim($this->request->post('username', ''));
        $password = $this->request->post('password', '');

        if (empty($username) || empty($password)) {
            $this->setFlash('error', 'Please enter username and password.');
            $this->redirect('/login');
            return;
        }

        $userModel = new User();

        // Check if LDAP is enabled
        if ($_ENV['LDAP_ENABLED'] ?? false) {
            $user = $this->authenticateLdap($username, $password);
        } else {
            $user = $userModel->authenticate($username, $password);
        }

        if (!$user) {
            $this->setFlash('error', 'Invalid username or password.');
            
            // Log failed attempt
            $auditLog = new AuditLog();
            $auditLog->log('login_failed', 'user', null, null, ['username' => $username]);
            
            $this->redirect('/login');
            return;
        }

        // Set session
        $_SESSION['user'] = $user;
        $_SESSION['correlation_id'] = bin2hex(random_bytes(8));

        // Log successful login
        $auditLog = new AuditLog();
        $auditLog->log('login_success', 'user', $user['id']);

        $this->setFlash('success', 'Welcome back, ' . htmlspecialchars($user['username']) . '!');
        $this->redirect('/');
    }

    public function logout(): void
    {
        if ($this->user) {
            $auditLog = new AuditLog();
            $auditLog->log('logout', 'user', $this->user['id']);
        }

        session_destroy();
        session_start();
        
        $this->setFlash('success', 'You have been logged out.');
        $this->redirect('/login');
    }

    private function authenticateLdap(string $username, string $password): ?array
    {
        $ldapHost = $_ENV['LDAP_HOST'] ?? '';
        $ldapPort = (int)($_ENV['LDAP_PORT'] ?? 389);
        $ldapBaseDn = $_ENV['LDAP_BASE_DN'] ?? '';

        if (empty($ldapHost) || empty($ldapBaseDn)) {
            return null;
        }

        $ldap = @ldap_connect($ldapHost, $ldapPort);
        if (!$ldap) {
            return null;
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        $userDn = "uid={$username},{$ldapBaseDn}";
        
        if (!@ldap_bind($ldap, $userDn, $password)) {
            ldap_close($ldap);
            return null;
        }

        // Get user info from LDAP
        $search = ldap_search($ldap, $ldapBaseDn, "(uid={$username})", ['mail', 'cn']);
        $entries = ldap_get_entries($ldap, $search);
        ldap_close($ldap);

        if ($entries['count'] === 0) {
            return null;
        }

        $email = $entries[0]['mail'][0] ?? "{$username}@ldap.local";

        // Create or update user in database
        $userModel = new User();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            $userId = $userModel->createLdapUser($username, $email);
            $user = $userModel->find($userId);
        } else {
            $userModel->updateLastLogin($user['id']);
        }

        unset($user['password_hash']);
        return $user;
    }
}
