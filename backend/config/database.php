<?php
/**
 * Database Configuration
 */
$env = [];
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

$getConfig = static function (string $key, string $default) use ($env): string {
    if (array_key_exists($key, $env)) {
        return $env[$key];
    }

    $value = getenv($key);
    return $value !== false && $value !== '' ? $value : $default;
};

return [
    'host' => $getConfig('DB_HOST', ''),
    'database' => $getConfig('DB_NAME', ''),
    'username' => $getConfig('DB_USER', ''),
    'password' => $getConfig('DB_PASS', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
