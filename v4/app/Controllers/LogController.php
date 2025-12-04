<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Logger;

class LogController
{
    /**
     * Display error logs
     */
    public function index(): void
    {
        // Get filter parameters
        $filters = [
            'level' => $_GET['level'] ?? '',
            'type' => $_GET['type'] ?? '',
            'lines' => (int)($_GET['lines'] ?? 50)
        ];

        // Get recent logs
        $allLogs = Logger::getRecentLogs($filters['lines']);
        
        // Filter logs if needed
        $logs = [];
        foreach ($allLogs as $log) {
            // Skip empty lines and separators
            if (empty(trim($log)) || strpos($log, '---') === 0 || strpos($log, '===') === 0) {
                continue;
            }
            
            // Apply filters
            if (!empty($filters['level']) && strpos($log, '[' . $filters['level'] . ']') === false) {
                continue;
            }
            
            if (!empty($filters['type']) && strpos($log, '[' . $filters['type'] . ']') === false) {
                continue;
            }
            
            $logs[] = $log;
        }

        // Calculate stats
        $stats = [
            'total' => count($logs),
            'critical' => 0,
            'warnings' => 0,
            'database' => 0
        ];

        foreach ($logs as $log) {
            if (strpos($log, '[CRITICAL]') !== false || strpos($log, '[FATAL]') !== false) {
                $stats['critical']++;
            }
            if (strpos($log, '[WARNING]') !== false) {
                $stats['warnings']++;
            }
            if (strpos($log, '[DATABASE]') !== false) {
                $stats['database']++;
            }
        }

        // Get log file info
        $logFile = Logger::getLogFile();
        $fileExists = is_string($logFile) && file_exists($logFile);
        $fileSize = $fileExists ? $this->formatBytes((int)filesize($logFile)) : 'N/A';

        View::render('logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'stats' => $stats,
            'logFile' => $logFile,
            'fileSize' => $fileSize
        ]);
    }

    /**
     * Clear error logs
     */
    public function clear(): void
    {
        Logger::clear();
        
        $_SESSION['success'] = 'Error log has been cleared successfully.';
        header('Location: /logs');
        exit;
    }

    /**
     * Download error log file
     */
    public function download(): void
    {
        $logFile = Logger::getLogFile();
        
        if (!is_string($logFile) || !file_exists($logFile)) {
            $_SESSION['error'] = 'Log file not found.';
            header('Location: /logs');
            exit;
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="partops-error-log-' . date('Y-m-d-His') . '.log"');
        header('Content-Length: ' . (string)filesize($logFile));
        readfile($logFile);
        exit;
    }

    /**
     * Format bytes to human-readable size
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
