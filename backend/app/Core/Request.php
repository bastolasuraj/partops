<?php
namespace App\Core;

class Request
{
    private array $data;
    private array $query;
    
    public function __construct()
    {
        $this->query = $_GET;
        
        // Parse JSON body for POST/PUT requests
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $this->data = json_decode($json, true) ?? [];
        } else {
            $this->data = $_POST;
        }
    }
    
    public function all(): array
    {
        return $this->data;
    }
    
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }
    
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }
    
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }
    
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    public static function uri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }
}
