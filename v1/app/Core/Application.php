<?php
/**
 * Application Core
 * 
 * Main application class that handles request/response lifecycle
 */

declare(strict_types=1);

namespace PartOps\Core;

use PartOps\Config\Config;
use PDO;

class Application
{
    private Config $config;
    private PDO $db;
    private Router $router;

    public function __construct(Config $config, PDO $db, Router $router)
    {
        $this->config = $config;
        $this->db = $db;
        $this->router = $router;
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            // Dispatch the request
            $this->router->dispatch($method, $uri);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle exceptions
     */
    private function handleException(\Exception $e): void
    {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());

        http_response_code(500);

        if ($this->config->getBool('APP_DEBUG', false)) {
            echo "<h1>Error</h1>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            echo "<h1>Internal Server Error</h1>";
            echo "<p>An error occurred. Please try again later.</p>";
        }
    }

    /**
     * Get configuration
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get database connection
     */
    public function getDb(): PDO
    {
        return $this->db;
    }
}
