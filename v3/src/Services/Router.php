<?php
declare(strict_types=1);

namespace PartOps\Services;

class Router
{
    private array $routes = [];
    private array $middleware = [];

    public function get(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array $handler, array $middleware): self
    {
        $pattern = $this->pathToPattern($path);
        $this->routes[$method][$pattern] = [
            'handler' => $handler,
            'middleware' => $middleware,
            'path' => $path
        ];
        return $this;
    }

    private function pathToPattern(string $path): string
    {
        return '#^' . preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path) . '$#';
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$method])) {
            $this->notFound();
            return;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    if (!$middleware->handle()) {
                        return;
                    }
                }

                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                $controller->$action(...array_values($params));
                return;
            }
        }

        $this->notFound();
    }

    private function notFound(): void
    {
        http_response_code(404);
        echo '404 - Page Not Found';
    }
}
