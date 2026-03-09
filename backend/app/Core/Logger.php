<?php
namespace App\Core;

class Logger
{
    private static string $logFile = __DIR__ . '/../../storage/logs/error.log';
    
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }
    
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }
    
    private static function log(string $level, string $message, array $context = []): void
    {
        try {
            // Ensure log directory exists
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                if (!mkdir($logDir, 0777, true) && !is_dir($logDir)) {
                    error_log("Failed to create log directory: {$logDir}");
                    return;
                }
            }
            
            // Make sure directory is writable
            if (!is_writable($logDir)) {
                chmod($logDir, 0777);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
            $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
            
            // Try to write to file
            $result = @file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
            
            if ($result === false) {
                // Fallback to error_log if file write fails
                error_log("[Logger] Failed to write to {self::$logFile}: {$message}");
            }
            
            // Also write to PHP error log for critical errors
            if ($level === 'ERROR') {
                error_log("[PartPal] {$message}" . ($contextStr ? " {$contextStr}" : ''));
            }
        } catch (\Exception $e) {
            error_log("Logger exception: " . $e->getMessage());
        }
    }
    
    public static function logRequest(): void
    {
        try {
            $data = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none'
            ];
            self::info('Incoming request', $data);
        } catch (\Exception $e) {
            error_log("Failed to log request: " . $e->getMessage());
        }
    }
}
