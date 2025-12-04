<?php
/**
 * Fix File Permissions
 * 
 * Run this script to fix storage directory permissions
 * php scripts/fix-permissions.php
 */

echo "PartOps v4 - Permission Fix Script\n";
echo str_repeat('=', 60) . "\n\n";

$baseDir = dirname(__DIR__);
$errors = [];
$fixed = [];

// Directories to fix
$directories = [
    'storage',
    'storage/logs',
    'storage/cache',
    'storage/uploads'
];

// Fix directory permissions
echo "Fixing directory permissions...\n";
foreach ($directories as $dir) {
    $path = $baseDir . '/' . $dir;
    if (is_dir($path)) {
        if (chmod($path, 0777)) {
            echo "  ✓ $dir (0777)\n";
            $fixed[] = $dir;
        } else {
            echo "  ✗ $dir (failed)\n";
            $errors[] = "Could not chmod $dir";
        }
    } else {
        echo "  ⊘ $dir (not found)\n";
        // Try to create it
        if (mkdir($path, 0777, true)) {
            echo "  ✓ $dir (created)\n";
            $fixed[] = $dir;
        } else {
            $errors[] = "Could not create $dir";
        }
    }
}

// Fix log file
echo "\nFixing log file...\n";
$logFile = $baseDir . '/storage/logs/err.log';
if (!file_exists($logFile)) {
    if (touch($logFile)) {
        echo "  ✓ Created err.log\n";
        $fixed[] = 'err.log (created)';
    } else {
        echo "  ✗ Could not create err.log\n";
        $errors[] = "Could not create err.log";
    }
}

if (file_exists($logFile)) {
    if (chmod($logFile, 0666)) {
        echo "  ✓ err.log (0666)\n";
        $fixed[] = 'err.log permissions';
    } else {
        echo "  ✗ err.log (chmod failed)\n";
        $errors[] = "Could not chmod err.log";
    }
}

// Summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 60) . "\n";
echo "Fixed: " . count($fixed) . " items\n";
echo "Errors: " . count($errors) . " items\n\n";

if (empty($errors)) {
    echo "✓ All permissions fixed successfully!\n\n";
    echo "Current permissions:\n";
    echo "  storage/: " . substr(sprintf('%o', fileperms($baseDir . '/storage')), -4) . "\n";
    echo "  storage/logs/: " . substr(sprintf('%o', fileperms($baseDir . '/storage/logs')), -4) . "\n";
    if (file_exists($logFile)) {
        echo "  storage/logs/err.log: " . substr(sprintf('%o', fileperms($logFile)), -4) . "\n";
    }
    echo "\nYou can now access the application.\n";
} else {
    echo "✗ Some errors occurred:\n\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }
    echo "\nYou may need to run this script with elevated permissions:\n";
    echo "  sudo php scripts/fix-permissions.php\n";
    echo "\nOr manually fix permissions:\n";
    echo "  chmod -R 777 storage/\n";
    echo "  chmod 666 storage/logs/err.log\n";
}

echo "\n";
