<?php
/**
 * Router
 * 
 * Handle HTTP routing and dispatch to controllers
 */

declare(strict_types=1);

namespace PartOps\Core;

class Router
{
    private array $routes = [];

    /**
     * Register GET route
     */
    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register POST route
     */
    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Add route to registry
     */
    private function addRoute(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->convertToPattern($path)
        ];
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToPattern(string $path): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // No route found
        $this->notFound();
    }

    /**
     * Call controller handler
     */
    private function callHandler(array $handler, array $params): void
    {
        [$controllerClass, $method] = $handler;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException(
                "Method {$method} not found in {$controllerClass}"
            );
        }

        call_user_func_array([$controller, $method], $params);
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        echo "<p>The requested page could not be found.</p>";
    }
}
