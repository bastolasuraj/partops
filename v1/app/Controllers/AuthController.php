<?php
/**
 * Authentication Controller
 * 
 * Handle user login and logout
 */

declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Core\Controller;
use PartOps\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // Redirect if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }

        $data = [
            'title' => 'Login',
            'csrf_token' => $this->generateCsrf()
        ];

        $this->view('auth.login', $data);
    }

    /**
     * Process login
     */
    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $username = $this->post('username');
        $password = $this->post('password');

        if (empty($username) || empty($password)) {
            $this->json(['error' => 'Username and password required'], 400);
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !$user['is_active']) {
            $this->json(['error' => 'Invalid credentials'], 401);
        }

        // Verify password for local users
        if ($user['auth_source'] === 'local') {
            if (!password_verify($password, $user['password_hash'])) {
                $this->json(['error' => 'Invalid credentials'], 401);
            }
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        $this->redirect('/');
    }

    /**
     * Process logout
     */
    public function logout(): void
    {
        session_destroy();
        $this->redirect('/login');
    }
}
