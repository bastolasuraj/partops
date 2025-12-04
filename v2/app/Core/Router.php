<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router Class - Handles URL routing
 * This file should be moved to: app/Core/Router.php
 */
class Router
{
    private Request $request;
    private Response $response;
    private array $routes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, string $callback): void
    {
        $this->routes['GET'][$path] = $callback;
    }

    public function post(string $path, string $callback): void
    {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve(): void
    {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();
        
        // Debug logging
        if ($_ENV['APP_DEBUG'] ?? false) {
            error_log("Router: Method=$method, Path=$path");
        }
        
        $callback = $this->matchRoute($method, $path);
        
        if ($callback === null) {
            // Debug: log available routes
            if ($_ENV['APP_DEBUG'] ?? false) {
                error_log("Router: No match found. Available routes for $method:");
                foreach ($this->routes[$method] ?? [] as $route => $handler) {
                    error_log("  - $route => $handler");
                }
            }
            $this->response->setStatusCode(404);
            echo $this->renderView('errors/404');
            return;
        }

        // Parse controller@method format
        [$controllerName, $methodName] = explode('@', $callback['handler']);
        
        // Build full controller class name
        $controllerClass = "App\\Controllers\\{$controllerName}";
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass($this->request, $this->response);
        
        if (!method_exists($controller, $methodName)) {
            throw new \Exception("Method {$methodName} not found in {$controllerClass}");
        }

        // Call the controller method with route parameters
        call_user_func_array([$controller, $methodName], $callback['params']);
    }

    private function matchRoute(string $method, string $path): ?array
    {
        $routes = $this->routes[$method] ?? [];
        
        foreach ($routes as $routePath => $handler) {
            $pattern = $this->convertToRegex($routePath);
            
            if (preg_match($pattern, $path, $matches)) {
                // Remove the full match, keep only named groups
                array_shift($matches);
                
                return [
                    'handler' => $handler,
                    'params' => $matches
                ];
            }
        }
        
        return null;
    }

    private function convertToRegex(string $path): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function renderView(string $view): string
    {
        $viewPath = BASE_PATH . '/app/Views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            return '<h1>404 - Page Not Found</h1>';
        }

        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
}
