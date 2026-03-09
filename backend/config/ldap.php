<?php
/**
 * LDAP Configuration
 */

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

return [
    // LDAP Server Settings
    'host' => getenv('LDAP_HOST') ?: 'ldap://Coral.FCCL.local',
    'port' => (int)(getenv('LDAP_PORT') ?: 389),
    'use_ssl' => getenv('LDAP_USE_SSL') === 'true',
    'use_tls' => getenv('LDAP_USE_TLS') === 'true',
    
    // Base DN for user searches
    'base_dn' => getenv('LDAP_BASE_DN') ?: 'DC=FCCL,DC=local',
    
    // User search settings
    'user_search_attribute' => getenv('LDAP_USER_SEARCH_ATTR') ?: 'sAMAccountName',
    'user_filter' => getenv('LDAP_USER_FILTER') ?: '(&(objectClass=user)(objectCategory=person))',
    
    // Security Group (for future use)
    // Leave empty to allow all authenticated users
    'required_group' => getenv('LDAP_REQUIRED_GROUP') ?: '',
    'group_attribute' => getenv('LDAP_GROUP_ATTR') ?: 'memberOf',

    // Allowed groups (comma-separated). Defaults to PAMAdmin and PAMUser.
    // Example: LDAP_ALLOWED_GROUPS="CN=PAMAdmin,OU=Security,OU=Groups,OU=Corp,DC=FCCL,DC=local,CN=PAMUser,OU=Security,OU=Groups,OU=Corp,DC=FCCL,DC=local"
    'allowed_groups' => (function () {
        $defaultGroups = [
            'CN=PAMAdmin,OU=Security,OU=Groups,OU=Corp,DC=FCCL,DC=local',
            'CN=PAMUser,OU=Security,OU=Groups,OU=Corp,DC=FCCL,DC=local',
        ];

        $envGroups = getenv('LDAP_ALLOWED_GROUPS') ?: '';
        $parsed = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', $envGroups))));

        return empty($parsed) ? $defaultGroups : $parsed;
    })(),

    // Role mapping groups
    'admin_group' => getenv('LDAP_ADMIN_GROUP') ?: 'CN=PAMAdmin,OU=Security,OU=Groups,OU=Corp,DC=FCCL,DC=local',
    'user_group' => getenv('LDAP_USER_GROUP') ?: 'CN=PAMUser,OU=Security,OU=Groups,OU=Corp,DC=FCCL,DC=local',
    
    // Bind credentials (optional - for searching)
    // If empty, will attempt anonymous bind or direct user bind
    'bind_dn' => getenv('LDAP_BIND_DN') ?: '',
    'bind_password' => getenv('LDAP_BIND_PASSWORD') ?: '',
    
    // Additional user attributes to retrieve
    'user_attributes' => [
        'displayName',
        'givenName',
        'mail',
        'sAMAccountName',
        'memberOf',
        'distinguishedName',
    ],
    
    // Session settings
    'session_lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 3600), // 1 hour in seconds
];
