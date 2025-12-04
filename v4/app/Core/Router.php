<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $path = $this->basePath . $path;
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Strip the app's base path so routes are matched correctly when hosted in a subdirectory.
        // APP_URL contains the expected base; fall back to script directory heuristics (handles /public).
        $baseFromEnv = '';
        if (!empty($_ENV['APP_URL'])) {
            $baseFromEnv = rtrim(parse_url($_ENV['APP_URL'], PHP_URL_PATH) ?: '', '/');
        }

        if ($baseFromEnv !== '' && strpos($uri, $baseFromEnv) === 0) {
            $uri = substr($uri, strlen($baseFromEnv));
        } else {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            $appBase = preg_replace('#/public$#', '', $scriptDir); // remove trailing /public if present

            if ($appBase !== '' && $appBase !== '/' && strpos($uri, $appBase) === 0) {
                $uri = substr($uri, strlen($appBase));
            } elseif ($scriptDir !== '' && $scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
                // Fallback: strip the full script dir (older behavior)
                $uri = substr($uri, strlen($scriptDir));
            }
        }
        
        // Ensure URI starts with /
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        $this->render404();
    }

    private function matchPath(string $routePath, string $uri, &$params = []): bool
    {
        $params = [];
        
        // Convert route path to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$controller, $method] = $handler;
            $controllerInstance = new $controller();
            call_user_func_array([$controllerInstance, $method], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }

    private function render404(): void
    {
        if (file_exists(__DIR__ . '/../Views/errors/404.php')) {
            View::render('errors/404');
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }
}
