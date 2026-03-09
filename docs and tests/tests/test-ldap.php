<?php
/**
 * LDAP Configuration Test Script
 * This script helps you test and configure your LDAP connection
 */

echo "=== LDAP Configuration Test ===\n\n";

// Step 1: Check if LDAP extension is loaded
echo "1. Checking LDAP extension: ";
if (extension_loaded('ldap')) {
    echo "✓ LOADED\n\n";
} else {
    echo "✗ NOT LOADED\n";
    echo "   Please enable the LDAP extension in php.ini\n";
    exit(1);
}

// Step 2: Get your configuration
echo "2. Please provide your LDAP configuration:\n\n";

// Prompt for configuration
echo "Enter your domain controller hostname (e.g., dc01.company.local): ";
$host = trim(fgets(STDIN));

echo "Enter your domain (e.g., company.local): ";
$domain = trim(fgets(STDIN));

echo "Enter your base DN (e.g., DC=company,DC=local): ";
$baseDn = trim(fgets(STDIN));

echo "Enter a test username (e.g., sbastola): ";
$username = trim(fgets(STDIN));

echo "Enter password for $username: ";
// Hide password input on Windows
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $password = trim(fgets(STDIN));
} else {
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
}

echo "\n3. Testing LDAP connection...\n";

// Test connection
$ldapUri = "ldap://{$host}:389";
echo "   Connecting to: $ldapUri\n";

$conn = @ldap_connect($ldapUri);
if (!$conn) {
    echo "   ✗ Failed to connect to LDAP server\n";
    exit(1);
}

ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

echo "   ✓ Connected to LDAP server\n\n";

// Test different authentication methods
echo "4. Testing authentication methods:\n\n";

// Method 1: Direct bind with username@domain
echo "   Method 1: UPN format (username@domain)\n";
$upn = $username . '@' . $domain;
echo "   Trying: $upn\n";
if (@ldap_bind($conn, $upn, $password)) {
    echo "   ✓ SUCCESS with UPN format!\n";
    echo "   Use this in your .env:\n";
    echo "   LDAP_USER_SEARCH_ATTR=userPrincipalName\n\n";
    $authMethod = 'upn';
} else {
    echo "   ✗ Failed: " . ldap_error($conn) . "\n\n";
    $authMethod = null;
}

// Method 2: Search and bind
if (!$authMethod) {
    echo "   Method 2: Search for user DN\n";
    
    // Try anonymous bind first
    if (@ldap_bind($conn)) {
        echo "   ✓ Anonymous bind successful\n";
        
        $searchFilter = "(&(objectClass=user)(objectCategory=person)(sAMAccountName=$username))";
        echo "   Searching for: $searchFilter\n";
        
        $search = @ldap_search($conn, $baseDn, $searchFilter, ['dn', 'displayName', 'mail', 'sAMAccountName']);
        
        if ($search) {
            $entries = ldap_get_entries($conn, $search);
            
            if ($entries['count'] > 0) {
                $userDn = $entries[0]['dn'];
                echo "   ✓ Found user: $userDn\n";
                
                // Try to bind with found DN
                $conn2 = ldap_connect($ldapUri);
                ldap_set_option($conn2, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($conn2, LDAP_OPT_REFERRALS, 0);
                
                if (@ldap_bind($conn2, $userDn, $password)) {
                    echo "   ✓ SUCCESS with DN bind!\n";
                    echo "   Use this in your .env:\n";
                    echo "   LDAP_USER_SEARCH_ATTR=sAMAccountName\n\n";
                    $authMethod = 'search';
                    
                    // Display user info
                    echo "   User Information:\n";
                    echo "   - Display Name: " . ($entries[0]['displayname'][0] ?? 'N/A') . "\n";
                    echo "   - Email: " . ($entries[0]['mail'][0] ?? 'N/A') . "\n";
                    echo "   - Username: " . ($entries[0]['samaccountname'][0] ?? 'N/A') . "\n\n";
                } else {
                    echo "   ✗ Failed to bind with user DN: " . ldap_error($conn2) . "\n\n";
                }
                
                @ldap_unbind($conn2);
            } else {
                echo "   ✗ User not found in directory\n\n";
            }
        } else {
            echo "   ✗ Search failed: " . ldap_error($conn) . "\n\n";
        }
    } else {
        echo "   ✗ Anonymous bind failed: " . ldap_error($conn) . "\n";
        echo "   You may need a service account for searching\n\n";
    }
}

// Method 3: Domain\username format
if (!$authMethod) {
    echo "   Method 3: Domain\\username format\n";
    $domainUser = strtoupper(explode('.', $domain)[0]) . '\\' . $username;
    echo "   Trying: $domainUser\n";
    
    $conn3 = ldap_connect($ldapUri);
    ldap_set_option($conn3, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conn3, LDAP_OPT_REFERRALS, 0);
    
    if (@ldap_bind($conn3, $domainUser, $password)) {
        echo "   ✓ SUCCESS with domain\\username format!\n";
        echo "   Use this in your .env:\n";
        echo "   LDAP_USER_SEARCH_ATTR=sAMAccountName\n\n";
        $authMethod = 'domain';
    } else {
        echo "   ✗ Failed: " . ldap_error($conn3) . "\n\n";
    }
    
    @ldap_unbind($conn3);
}

@ldap_unbind($conn);

// Generate .env configuration
if ($authMethod) {
    echo "\n5. ✓ LDAP authentication is working!\n\n";
    echo "=== Recommended .env Configuration ===\n\n";
    echo "# LDAP Configuration\n";
    echo "LDAP_HOST=ldap://{$host}\n";
    echo "LDAP_PORT=389\n";
    echo "LDAP_USE_SSL=false\n";
    echo "LDAP_USE_TLS=false\n";
    echo "LDAP_BASE_DN={$baseDn}\n";
    echo "LDAP_USER_SEARCH_ATTR=sAMAccountName\n";
    echo "LDAP_USER_FILTER=(&(objectClass=user)(objectCategory=person))\n";
    echo "LDAP_REQUIRED_GROUP=\n";
    echo "LDAP_GROUP_ATTR=memberOf\n";
    echo "LDAP_BIND_DN=\n";
    echo "LDAP_BIND_PASSWORD=\n";
    echo "SESSION_LIFETIME=3600\n\n";
    
    echo "Copy this configuration to: backend/.env\n";
} else {
    echo "\n5. ✗ Could not authenticate with any method\n";
    echo "   Please check:\n";
    echo "   - Username and password are correct\n";
    echo "   - User exists in the specified base DN\n";
    echo "   - Network connectivity to domain controller\n";
    echo "   - Firewall allows LDAP traffic (port 389)\n";
}
