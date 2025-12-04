<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

echo "<h1>PartOps v2 Test</h1>";
echo "<p>If you see this, PHP is working!</p>";

echo "<h2>Environment Check</h2>";
echo "<pre>";
echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'not set') . "\n";
echo "APP_DEBUG: " . ($_ENV['APP_DEBUG'] ?? 'not set') . "\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'not set') . "\n";
echo "</pre>";

echo "<h2>Database Connection Test</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    echo "<p style='color:green'>✓ Database connection successful!</p>";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Found " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>File System Check</h2>";
echo "<pre>";
echo "bootstrap.php exists: " . (file_exists(BASE_PATH . '/app/bootstrap.php') ? 'YES' : 'NO') . "\n";
echo "Application.php exists: " . (file_exists(BASE_PATH . '/app/Core/Application.php') ? 'YES' : 'NO') . "\n";
echo "HomeController.php exists: " . (file_exists(BASE_PATH . '/app/Controllers/HomeController.php') ? 'YES' : 'NO') . "\n";
echo "</pre>";

echo "<h2>Try These Links</h2>";
echo "<ul>";
echo "<li><a href='/partops/v2/'>Home (via router)</a></li>";
echo "<li><a href='/partops/v2/login'>Login page</a></li>";
echo "<li><a href='/partops/v2/debug.php'>Debug info</a></li>";
echo "</ul>";
