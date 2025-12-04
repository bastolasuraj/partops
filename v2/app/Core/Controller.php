<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base Controller Class
 * This file should be moved to: app/Core/Controller.php
 */
abstract class Controller
{
    protected Request $request;
    protected Response $response;
    protected ?array $user = null;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->user = $_SESSION['user'] ?? null;
    }

    protected function view(string $view, array $data = []): void
    {
        // Extract data to make variables available in view
        extract($data);
        
        // Add common variables
        $csrfToken = $_SESSION['csrf_token'] ?? '';
        $user = $this->user;
        $flash = $this->getFlash();
        
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$view} not found");
        }

        include $viewPath;
    }

    protected function renderWithLayout(string $view, array $data = [], string $layout = 'main'): void
    {
        // Render the view content first
        extract($data);
        $csrfToken = $_SESSION['csrf_token'] ?? '';
        $user = $this->user;
        $flash = $this->getFlash();
        
        ob_start();
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        }
        $content = ob_get_clean();
        
        // Now render the layout with the content
        $layoutPath = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
        if (file_exists($layoutPath)) {
            include $layoutPath;
        } else {
            echo $content;
        }
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        $this->response->json($data, $statusCode);
    }

    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    protected function back(): void
    {
        $this->response->back();
    }

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    protected function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    protected function isAdmin(): bool
    {
        return ($this->user['role'] ?? '') === 'admin';
    }

    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->setFlash('error', 'Please log in to continue.');
            $this->redirect('/login');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            $this->setFlash('error', 'Access denied. Admin privileges required.');
            $this->redirect('/');
        }
    }

    protected function validateCsrf(): bool
    {
        if (!$this->request->validateCsrf()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            return false;
        }
        return true;
    }

    protected function generateIdempotencyKey(): string
    {
        return 'idem-' . date('Ymd') . '-' . bin2hex(random_bytes(4));
    }

    protected function validate(array $rules): array
    {
        $errors = [];
        $data = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->request->input($field);
            $ruleList = explode('|', $rule);
            
            foreach ($ruleList as $r) {
                $parts = explode(':', $r);
                $ruleName = $parts[0];
                $ruleParam = $parts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = ucfirst($field) . ' is required.';
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email.';
                        }
                        break;
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be a number.';
                        }
                        break;
                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$ruleParam) {
                            $errors[$field][] = ucfirst($field) . " must be at least {$ruleParam} characters.";
                        }
                        break;
                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$ruleParam) {
                            $errors[$field][] = ucfirst($field) . " must not exceed {$ruleParam} characters.";
                        }
                        break;
                }
            }
            
            $data[$field] = $value;
        }
        
        if (!empty($errors)) {
            $_SESSION['validation_errors'] = $errors;
            $_SESSION['old_input'] = $data;
        }
        
        return ['valid' => empty($errors), 'data' => $data, 'errors' => $errors];
    }

    protected function old(string $field, mixed $default = ''): mixed
    {
        return $_SESSION['old_input'][$field] ?? $default;
    }

    protected function errors(string $field): array
    {
        $errors = $_SESSION['validation_errors'][$field] ?? [];
        unset($_SESSION['validation_errors'][$field]);
        return $errors;
    }
}
