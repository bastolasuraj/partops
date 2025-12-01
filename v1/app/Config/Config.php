<?php
/**
 * Configuration Manager
 * 
 * Load and manage environment configuration
 */

declare(strict_types=1);

namespace PartOps\Config;

class Config
{
    private array $config = [];

    private function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Load configuration from .env file
     */
    public static function load(string $envPath): self
    {
        $config = [];

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    
                    $config[$key] = $value;
                }
            }
        }

        return new self($config);
    }

    /**
     * Get configuration value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get boolean configuration value
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get integer configuration value
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key);
        
        if ($value === null) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }
}
