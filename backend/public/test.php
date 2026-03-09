<?php
/**
 * Simple test file to verify PHP and logging are working
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test 1: Basic PHP
$tests = [
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s'),
];

// Test 2: File writing
$logDir = __DIR__ . '/../storage/logs';
$testFile = $logDir . '/test.txt';

try {
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $writeResult = file_put_contents($testFile, "Test write at " . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
    $tests['log_directory'] = $logDir;
    $tests['log_writable'] = is_writable($logDir);
    $tests['test_file_write'] = $writeResult !== false;
    $tests['test_file_exists'] = file_exists($testFile);
    
    if (file_exists($testFile)) {
        $tests['test_file_size'] = filesize($testFile);
        $tests['test_file_content'] = file_get_contents($testFile);
    }
} catch (Exception $e) {
    $tests['file_error'] = $e->getMessage();
}

// Test 3: Database config
try {
    $dbConfig = require __DIR__ . '/../config/database.php';
    $tests['database_config'] = [
        'host' => $dbConfig['host'],
        'database' => $dbConfig['database'],
        'username' => $dbConfig['username'],
    ];
} catch (Exception $e) {
    $tests['database_config_error'] = $e->getMessage();
}

// Test 4: Autoloader
try {
    require_once __DIR__ . '/../app/Core/Logger.php';
    $tests['logger_class_exists'] = class_exists('App\Core\Logger');
    
    // Try to use logger
    \App\Core\Logger::info('Test log entry from test.php');
    $tests['logger_test'] = 'Called Logger::info()';
} catch (Exception $e) {
    $tests['logger_error'] = $e->getMessage();
}

// Test 5: Request info
$tests['request'] = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'http_origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none',
];

echo json_encode([
    'success' => true,
    'message' => 'Backend test successful',
    'tests' => $tests
], JSON_PRETTY_PRINT);
