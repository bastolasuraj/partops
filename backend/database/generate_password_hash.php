<?php
/**
 * Generate password hash for PAM users
 * Usage: php generate_password_hash.php "YourPassword"
 */

if ($argc < 2) {
    echo "Usage: php generate_password_hash.php \"YourPassword\"\n";
    echo "\nExample:\n";
    echo "  php generate_password_hash.php \"P@rtsPa\$\$12\"\n\n";
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "\nPassword: {$password}\n";
echo "Hash: {$hash}\n\n";
echo "Copy this hash to your SQL INSERT statement:\n";
echo "'{$hash}'\n\n";

// Verify the hash works
if (password_verify($password, $hash)) {
    echo "✓ Hash verified successfully!\n\n";
} else {
    echo "✗ Hash verification failed!\n\n";
}
