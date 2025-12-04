<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Response Class - Handles HTTP responses
 * This file should be moved to: app/Core/Response.php
 */
class Response
{
    private int $statusCode = 200;

    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
        http_response_code($code);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function redirect(string $url): void
    {
        // If URL doesn't start with http:// or https://, prepend base URL
        if (!preg_match('/^https?:\/\//', $url)) {
            $baseUrl = $_ENV['APP_URL'] ?? '';
            if (empty($baseUrl)) {
                // Auto-detect base URL
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                $baseUrl = $protocol . '://' . $host . dirname(dirname($scriptName));
            }
            $baseUrl = rtrim($baseUrl, '/');
            $url = $baseUrl . $url;
        }
        
        header("Location: {$url}");
        exit;
    }

    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function setHeader(string $name, string $value): void
    {
        header("{$name}: {$value}");
    }

    public function setCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): void {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Strict'
        ]);
    }

    public function download(string $filePath, string $fileName = null): void
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404);
            echo 'File not found';
            return;
        }

        $fileName = $fileName ?? basename($filePath);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }
}
