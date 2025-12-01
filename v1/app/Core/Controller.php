<?php
/**
 * Base Controller
 * 
 * Parent class for all controllers
 */

declare(strict_types=1);

namespace PartOps\Core;

class Controller
{
    /**
     * Render a view
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }

        require $viewPath;
    }

    /**
     * Return JSON response
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Get POST data
     */
    protected function post(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }

        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     */
    protected function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->post('_csrf_token');
        
        if (!isset($_SESSION['_csrf_token']) || $token !== $_SESSION['_csrf_token']) {
            return false;
        }

        return true;
    }

    /**
     * Generate CSRF token
     */
    protected function generateCsrf(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Check if user is authenticated
     */
    protected function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    /**
     * Check if user has admin role
     */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1>";
            echo "<p>You do not have permission to access this resource.</p>";
            exit;
        }
    }
}
