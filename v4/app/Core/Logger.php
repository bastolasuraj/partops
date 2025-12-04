<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Logger Class
 * 
 * Handles error logging for PHP errors, MySQL errors, and application errors
 */
class Logger
{
    private static ?string $logFile = null;
    private static bool $initialized = false;

    /**
     * Initialize the logger
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Set log file path
        self::$logFile = dirname(__DIR__, 2) . '/storage/logs/err.log';

        // Ensure logs directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Ensure log file exists and is writable
        if (!file_exists(self::$logFile)) {
            @touch(self::$logFile);
            @chmod(self::$logFile, 0666);
        }
        
        // Check if log file is writable
        if (!is_writable(self::$logFile)) {
            // Try to make it writable
            @chmod(self::$logFile, 0666);
            
            // If still not writable, disable logging to prevent errors
            if (!is_writable(self::$logFile)) {
                error_log('PartOps Logger: Cannot write to log file: ' . self::$logFile);
                self::$logFile = null; // Disable file logging
                return;
            }
        }

        // Set up PHP error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set up exception handler
        set_exception_handler([self::class, 'handleException']);
        
        // Set up shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        self::$initialized = true;
    }

    /**
     * Custom error handler
     */
    public static function handleError(
        int $errno,
        string $errstr,
        string $errfile = '',
        int $errline = 0
    ): bool {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED',
        ];

        $level = $errorTypes[$errno] ?? 'UNKNOWN';
        
        self::log(
            'PHP',
            $level,
            $errstr,
            [
                'file' => $errfile,
                'line' => $errline,
                'trace' => self::getShortTrace()
            ]
        );

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Custom exception handler
     */
    public static function handleException(\Throwable $exception): void
    {
        self::log(
            'EXCEPTION',
            'CRITICAL',
            $exception->getMessage(),
            [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );

        // Display user-friendly error page in production
        if ($_ENV['APP_ENV'] ?? 'production' === 'production') {
            http_response_code(500);
            echo self::getErrorPage();
        }
    }

    /**
     * Handle fatal errors on shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR
        ])) {
            self::log(
                'FATAL',
                'CRITICAL',
                $error['message'],
                [
                    'file' => $error['file'],
                    'line' => $error['line']
                ]
            );
        }
    }

    /**
     * Log a message
     */
    public static function log(
        string $type,
        string $level,
        string $message,
        array $context = []
    ): void {
        if (self::$logFile === null) {
            self::init();
        }

        // If still no log file (e.g., not writable), fallback to PHP error_log and return
        if (self::$logFile === null) {
            $fallback = sprintf('[%s] [%s] [%s] %s', date('Y-m-d H:i:s'), $level, $type, $message);
            if (!empty($context)) {
                $fallback .= ' Context: ' . json_encode($context, JSON_PRETTY_PRINT);
            }
            error_log($fallback);
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] [%s] [%s] %s\n",
            $timestamp,
            $level,
            $type,
            $message
        );

        // Add context if provided
        if (!empty($context)) {
            $logEntry .= "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }

        $logEntry .= str_repeat('-', 80) . "\n";

        // Write to log file
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log database errors
     */
    public static function logDatabase(string $message, array $context = []): void
    {
        self::log('DATABASE', 'ERROR', $message, $context);
    }

    /**
     * Log application errors
     */
    public static function logApp(string $message, string $level = 'ERROR', array $context = []): void
    {
        self::log('APP', $level, $message, $context);
    }

    /**
     * Log info messages
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', 'INFO', $message, $context);
    }

    /**
     * Log warning messages
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', 'WARNING', $message, $context);
    }

    /**
     * Log debug messages (only in development)
     */
    public static function debug(string $message, array $context = []): void
    {
        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            self::log('DEBUG', 'DEBUG', $message, $context);
        }
    }

    /**
     * Get a shortened stack trace
     */
    private static function getShortTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $result = [];
        
        foreach ($trace as $frame) {
            if (isset($frame['file']) && isset($frame['line'])) {
                $result[] = sprintf(
                    '%s:%d',
                    basename($frame['file']),
                    $frame['line']
                );
            }
        }
        
        return implode(' -> ', $result);
    }

    /**
     * Get user-friendly error page HTML
     */
    private static function getErrorPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - PartOps v4</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #ff7979;
            font-size: 48px;
            margin: 0 0 20px 0;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin: 0 0 30px 0;
        }
        a {
            display: inline-block;
            background: #f9ca24;
            color: #2c3e50;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        a:hover {
            background: #f6e58d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>⚠️ Oops!</h1>
        <p>Something went wrong. Our team has been notified and we're working on it.</p>
        <p>Please try again in a few moments.</p>
        <a href="<?= function_exists('url') ? url('/') : '/' ?>">Return Home</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Clear the log file
     */
    public static function clear(): void
    {
        if (self::$logFile && file_exists(self::$logFile)) {
            file_put_contents(self::$logFile, "# Log cleared at " . date('Y-m-d H:i:s') . "\n");
        }
    }

    /**
     * Get log file path
     */
    public static function getLogFile(): ?string
    {
        return self::$logFile;
    }

    /**
     * Read recent log entries
     */
    public static function getRecentLogs(int $lines = 50): array
    {
        if (!self::$logFile || !file_exists(self::$logFile)) {
            return [];
        }

        $file = new \SplFileObject(self::$logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        $logs = [];
        while (!$file->eof()) {
            $line = $file->current();
            if (!empty(trim($line))) {
                $logs[] = $line;
            }
            $file->next();
        }
        
        return $logs;
    }
}
