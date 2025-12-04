<?php
/**
 * Deployment Verification Script
 * 
 * Run this to verify the v4 deployment is configured correctly
 * php scripts/verify-deployment.php
 */

declare(strict_types=1);

echo "PartOps v4 - Deployment Verification\n";
echo str_repeat('=', 60) . "\n\n";

$checks = [];
$errors = [];

// Check 1: Composer autoloader
echo "1. Checking Composer autoloader... ";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "✓\n";
    $checks[] = 'Composer autoloader';
} else {
    echo "✗\n";
    $errors[] = 'Composer autoloader not found. Run: composer install';
}

// Check 2: .env file
echo "2. Checking .env file... ";
if (file_exists(__DIR__ . '/../.env')) {
    echo "✓\n";
    $checks[] = '.env file';
} else {
    echo "✗\n";
    $errors[] = '.env file not found. Copy from .env.example';
}

// Check 3: Storage directories
echo "3. Checking storage directories... ";
$dirs = ['storage/logs', 'storage/cache', 'storage/uploads'];
$allExist = true;
foreach ($dirs as $dir) {
    if (!is_dir(__DIR__ . '/../' . $dir)) {
        $allExist = false;
        $errors[] = "Directory missing: $dir";
    }
}
if ($allExist) {
    echo "✓\n";
    $checks[] = 'Storage directories';
} else {
    echo "✗\n";
}

// Check 4: Log file writable
echo "4. Checking log file permissions... ";
$logFile = __DIR__ . '/../storage/logs/err.log';
if (file_exists($logFile) && is_writable($logFile)) {
    echo "✓\n";
    $checks[] = 'Log file writable';
} else {
    echo "✗\n";
    $errors[] = 'Log file not writable. Run: chmod 666 storage/logs/err.log';
}

// Check 5: .htaccess files
echo "5. Checking .htaccess files... ";
$htaccessFiles = [
    __DIR__ . '/../.htaccess',
    __DIR__ . '/../public/.htaccess'
];
$allExist = true;
foreach ($htaccessFiles as $file) {
    if (!file_exists($file)) {
        $allExist = false;
        $errors[] = "Missing: " . basename(dirname($file)) . '/.htaccess';
    }
}
if ($allExist) {
    echo "✓\n";
    $checks[] = '.htaccess files';
} else {
    echo "✗\n";
}

// Check 6: Core classes
echo "6. Checking core classes... ";
$coreClasses = ['Router', 'Database', 'View', 'Model', 'Logger'];
$allExist = true;
foreach ($coreClasses as $class) {
    if (!file_exists(__DIR__ . "/../app/Core/{$class}.php")) {
        $allExist = false;
        $errors[] = "Missing core class: $class";
    }
}
if ($allExist) {
    echo "✓\n";
    $checks[] = 'Core classes';
} else {
    echo "✗\n";
}

// Check 7: Controllers
echo "7. Checking controllers... ";
$controllers = [
    'HomeController',
    'PartController',
    'SupplierController',
    'TechnicianController',
    'WorkOrderController',
    'CheckinController',
    'CheckoutController',
    'ReturnController',
    'LogController'
];
$allExist = true;
foreach ($controllers as $controller) {
    if (!file_exists(__DIR__ . "/../app/Controllers/{$controller}.php")) {
        $allExist = false;
        $errors[] = "Missing controller: $controller";
    }
}
if ($allExist) {
    echo "✓\n";
    $checks[] = 'Controllers';
} else {
    echo "✗\n";
}

// Check 8: Database connection
echo "8. Testing database connection... ";
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        
        try {
            $pdo = new PDO(
                'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );
            echo "✓\n";
            $checks[] = 'Database connection';
            
            // Check tables
            echo "9. Checking database tables... ";
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredTables = ['suppliers', 'technicians', 'parts', 'work_orders'];
            $missingTables = array_diff($requiredTables, $tables);
            
            if (empty($missingTables)) {
                echo "✓ (Found " . count($tables) . " tables)\n";
                $checks[] = 'Database tables';
            } else {
                echo "✗\n";
                $errors[] = 'Missing tables: ' . implode(', ', $missingTables) . '. Run: php scripts/migrate.php';
            }
        } catch (PDOException $e) {
            echo "✗\n";
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
    } else {
        echo "⊘ (skipped - no .env)\n";
    }
} else {
    echo "⊘ (skipped - no autoloader)\n";
}

// Check 10: Prototype files renamed
echo "10. Checking prototype files... ";
if (!file_exists(__DIR__ . '/../index.html') && 
    file_exists(__DIR__ . '/../_prototype-index.html')) {
    echo "✓\n";
    $checks[] = 'Prototype files renamed';
} else {
    echo "✗\n";
    $errors[] = 'Prototype HTML files not renamed. They may conflict with the app.';
}

// Summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "Passed: " . count($checks) . "\n";
echo "Failed: " . count($errors) . "\n\n";

if (empty($errors)) {
    echo "✓ All checks passed! Deployment is ready.\n\n";
    echo "Access your application at:\n";
    echo "https://php.sbastola.com/partops/v4/\n\n";
    echo "Test routes:\n";
    echo "  - / (home)\n";
    echo "  - /dashboard\n";
    echo "  - /parts\n";
    echo "  - /suppliers\n";
    echo "  - /logs\n";
} else {
    echo "✗ Issues found:\n\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
    echo "\nFix these issues and run this script again.\n";
}

echo "\n";
