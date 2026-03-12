<?php
namespace App\Core;

class Router
{
    private array $routes = [];
    
    public function get(string $path, $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }
    
    public function post(string $path, $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }
    
    public function put(string $path, $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }
    
    public function delete(string $path, $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }
    
    private function addRoute(string $method, string $path, $handler): void
    {
        // Convert route parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }
    
    public function dispatch(string $method, string $uri): void
    {
        // Log the raw request
        Logger::info('Router dispatch', [
            'raw_uri' => $uri,
            'method' => $method
        ]);
        
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Remove /api prefix if present
        $uri = preg_replace('#^/api#', '', $uri);
        
        // Ensure uri starts with /
        if (empty($uri) || $uri === '') {
            $uri = '/';
        }
        
        Logger::info('Router processing', [
            'processed_uri' => $uri,
            'method' => $method,
            'routes_count' => count($this->routes)
        ]);
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            if (preg_match($route['pattern'], $uri, $matches)) {
                Logger::info('Route matched', [
                    'uri' => $uri,
                    'pattern' => $route['pattern'],
                    'handler' => is_array($route['handler']) ? $route['handler'][0] . '::' . $route['handler'][1] : 'closure'
                ]);
                
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $handler = $route['handler'];
                
                // Handle closure
                if (is_callable($handler) && !is_array($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }
                
                // Handle controller array
                [$controllerClass, $action] = $handler;
                $controller = new $controllerClass();
                
                call_user_func_array([$controller, $action], $params);
                return;
            }
        }
        
        // No route found - log all attempted patterns
        $patterns = array_map(function($r) { return $r['pattern']; }, $this->routes);
        Logger::warning('Route not found', [
            'uri' => $uri,
            'method' => $method,
            'available_patterns' => $patterns
        ]);
        
        Response::json([
            'error' => 'Route not found',
        ], 404);
    }
}
