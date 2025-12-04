<?php
echo "<h1>Debug Info</h1>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "\nBase path calculation:\n";
$basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
echo "Base path: " . $basePath . "\n";
$path = $_SERVER['REQUEST_URI'] ?? '/';
$position = strpos($path, '?');
if ($position !== false) {
    $path = substr($path, 0, $position);
}
echo "Path before removal: " . $path . "\n";
if ($basePath !== '/' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
echo "Path after removal: " . ($path ?: '/') . "\n";
echo "</pre>";
