<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Request Class - Handles HTTP request data
 * This file should be moved to: app/Core/Request.php
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string
    {
        $path = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        
        // Remove base path if app is in subdirectory
        // SCRIPT_NAME is like /partops/v2/public/index.php
        // We need to remove /partops/v2 from the path
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        
        // Get the directory two levels up from index.php
        // /partops/v2/public/index.php -> /partops/v2/public -> /partops/v2
        $basePath = dirname(dirname($scriptName));
        
        if ($basePath !== '/' && $basePath !== '.' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        // Ensure path starts with /
        if (empty($path) || $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        return $path;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (isset($this->post[$key])) {
                $result[$key] = $this->post[$key];
            } elseif (isset($this->get[$key])) {
                $result[$key] = $this->get[$key];
            }
        }
        return $result;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function getHeader(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function getIp(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }

    public function getUserAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function validateCsrf(): bool
    {
        $token = $this->post('_csrf_token') ?? $this->getHeader('X-CSRF-Token');
        return $token !== null && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public function getJson(): ?array
    {
        $content = file_get_contents('php://input');
        if (empty($content)) {
            return null;
        }
        return json_decode($content, true);
    }
}
