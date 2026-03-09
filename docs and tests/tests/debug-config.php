<?php
echo "=== LDAP Config Debug ===\n\n";

$config = require __DIR__ . '/../../backend/config/ldap.php';

echo "Configuration loaded:\n";
echo "Host: " . $config['host'] . "\n";
echo "Port: " . $config['port'] . "\n";
echo "Base DN: " . $config['base_dn'] . "\n";
echo "User Search Attr: " . $config['user_search_attribute'] . "\n";
echo "\n";

// Test UPN construction
$baseDn = $config['base_dn'];
if (preg_match_all('/DC=([^,]+)/', $baseDn, $matches)) {
    $domain = implode('.', $matches[1]);
    $upn = 'sbastola@' . $domain;
    echo "Constructed UPN: $upn\n";
}
