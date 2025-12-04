<?php
/**
 * Test Logger Functionality
 * 
 * Run this script to test the error logging system:
 * php scripts/test-logger.php
 */

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Initialize Logger
App\Core\Logger::init();

echo "Testing PartOps v4 Error Logging System\n";
echo str_repeat('=', 50) . "\n\n";

// Test 1: Info logging
echo "Test 1: Info logging... ";
App\Core\Logger::info('Test info message', ['test' => 'info']);
echo "✓\n";

// Test 2: Warning logging
echo "Test 2: Warning logging... ";
App\Core\Logger::warning('Test warning message', ['test' => 'warning']);
echo "✓\n";

// Test 3: Application error logging
echo "Test 3: Application error logging... ";
App\Core\Logger::logApp('Test application error', 'ERROR', ['test' => 'app_error']);
echo "✓\n";

// Test 4: Database error logging
echo "Test 4: Database error logging... ";
App\Core\Logger::logDatabase('Test database error', ['query' => 'SELECT * FROM test']);
echo "✓\n";

// Test 5: Debug logging (only in development)
echo "Test 5: Debug logging... ";
App\Core\Logger::debug('Test debug message', ['test' => 'debug']);
echo "✓\n";

// Test 6: PHP error (warning)
echo "Test 6: PHP warning... ";
@trigger_error('Test PHP warning', E_USER_WARNING);
echo "✓\n";

// Test 7: PHP notice
echo "Test 7: PHP notice... ";
@trigger_error('Test PHP notice', E_USER_NOTICE);
echo "✓\n";

// Test 8: Exception handling
echo "Test 8: Exception handling... ";
try {
    throw new \Exception('Test exception');
} catch (\Exception $e) {
    App\Core\Logger::log('EXCEPTION', 'ERROR', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
echo "✓\n";

// Test 9: Get recent logs
echo "\nTest 9: Reading recent logs... ";
$logs = App\Core\Logger::getRecentLogs(10);
echo "Found " . count($logs) . " log entries ✓\n";

// Test 10: Log file info
echo "\nTest 10: Log file information... ";
$logFile = App\Core\Logger::getLogFile();
if (file_exists($logFile)) {
    $size = filesize($logFile);
    echo "✓\n";
    echo "  - Path: $logFile\n";
    echo "  - Size: " . number_format($size) . " bytes\n";
    echo "  - Writable: " . (is_writable($logFile) ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ (file not found)\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "All tests completed!\n";
echo "View logs at: http://localhost:8000/logs\n";
echo "Or check file: $logFile\n";
