<?php
namespace App\Core;

class LdapAuth
{
    private array $config;
    private $connection;
    
    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/ldap.php';
    }
    
    /**
     * Authenticate user with LDAP
     */
    public function authenticate(string $username, string $password): array|false
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        
        try {
            // Connect to LDAP server
            $this->connect();
            
            // Find user DN
            $userDn = $this->findUserDn($username);
            if (!$userDn) {
                Logger::warning('LDAP user not found', ['username' => $username]);
                return false;
            }
            
            // Attempt to bind with user credentials
            if (!@ldap_bind($this->connection, $userDn, $password)) {
                Logger::warning('LDAP authentication failed', [
                    'username' => $username,
                    'userDn' => $userDn,
                    'error' => ldap_error($this->connection)
                ]);
                return false;
            }
            
            Logger::info('LDAP bind successful', ['username' => $username, 'userDn' => $userDn]);
            
            // Get user details - if UPN format, search for the actual user entry
            $userDetails = $this->getUserDetails($userDn, $username);
            
            $allowedGroups = $this->config['allowed_groups'] ?? [];
            if (!empty($allowedGroups) && !$this->isUserInAnyGroup($userDetails, $allowedGroups)) {
                Logger::warning('LDAP user not in allowed groups', [
                    'username' => $username
                ]);
                return false;
            }

            // Check group membership if required
            if (!empty($this->config['required_group'])) {
                if (!$this->isUserInGroup($userDetails, $this->config['required_group'])) {
                    Logger::warning('LDAP user not in required group', [
                        'username' => $username,
                        'required_group' => $this->config['required_group']
                    ]);
                    return false;
                }
            }
            
            Logger::info('LDAP authentication successful', ['username' => $username]);

            $displayName = $userDetails['displayname'][0] ?? $username;
            $firstName = $userDetails['givenname'][0] ?? '';
            if ($firstName === '') {
                $firstName = $this->extractFirstName($displayName, $username);
            }

            $role = $this->determineRole($userDetails);

            return [
                'username' => $userDetails['samaccountname'][0] ?? $username,
                'display_name' => $displayName,
                'first_name' => $firstName,
                'email' => $userDetails['mail'][0] ?? '',
                'dn' => $userDn,
                'groups' => $this->extractGroups($userDetails),
                'role' => $role,
            ];
            
        } catch (\Exception $e) {
            Logger::error('LDAP authentication error', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        } finally {
            $this->disconnect();
        }
    }
    
    /**
     * Connect to LDAP server
     */
    private function connect(): void
    {
        $protocol = $this->config['use_ssl'] ? 'ldaps://' : 'ldap://';
        $host = $this->config['host'];
        
        // Remove protocol if already in host
        $host = preg_replace('/^(ldaps?:\/\/)/', '', $host);
        
        $ldapUri = $protocol . $host . ':' . $this->config['port'];
        
        $this->connection = ldap_connect($ldapUri);
        
        if (!$this->connection) {
            throw new \Exception('Could not connect to LDAP server');
        }
        
        // Set LDAP options
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->connection, LDAP_OPT_NETWORK_TIMEOUT, 10);
        
        // Start TLS if configured
        if ($this->config['use_tls'] && !$this->config['use_ssl']) {
            if (!@ldap_start_tls($this->connection)) {
                throw new \Exception('Could not start TLS: ' . ldap_error($this->connection));
            }
        }
    }
    
    /**
     * Find user DN by username
     */
    private function findUserDn(string $username): string|false
    {
        $isDebug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
        Logger::info('Finding user DN', $isDebug ? ['username' => $username] : []);
        
        // If bind credentials provided, use them for search
        if (!empty($this->config['bind_dn'])) {
            Logger::info('Using service account for search');
            if (!@ldap_bind($this->connection, $this->config['bind_dn'], $this->config['bind_password'])) {
                throw new \Exception('Could not bind with service account: ' . ldap_error($this->connection));
            }
        } else {
            // Try anonymous bind
            Logger::info('Attempting anonymous bind');
            if (!@ldap_bind($this->connection)) {
                // If anonymous bind fails, try to construct DN directly
                $upn = $this->constructUserDn($username);
                Logger::info('Anonymous bind failed, using UPN');
                return $upn;
            }
            Logger::info('Anonymous bind successful');
        }
        
        // Search for user — escape username to prevent LDAP injection.
        $allowedSearchAttrs = ['sAMAccountName', 'userPrincipalName', 'uid', 'mail'];
        $searchAttr = in_array($this->config['user_search_attribute'], $allowedSearchAttrs, true)
            ? $this->config['user_search_attribute']
            : 'sAMAccountName';
        $safeUsername = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $filter = "(&{$this->config['user_filter']}({$searchAttr}={$safeUsername}))";

        $isDebug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
        Logger::info('Searching for user', $isDebug ? ['filter' => $filter, 'base_dn' => $this->config['base_dn']] : []);
        
        $search = @ldap_search(
            $this->connection,
            $this->config['base_dn'],
            $filter,
            ['dn']
        );
        
        if (!$search) {
            Logger::warning('LDAP search failed', ['error' => ldap_error($this->connection)]);
            // Fallback to UPN
            return $this->constructUserDn($username);
        }
        
        $entries = ldap_get_entries($this->connection, $search);
        
        Logger::info('Search results', ['count' => $entries['count']]);

        if ($entries['count'] === 0) {
            Logger::warning('No users found in search, falling back to UPN');
            return $this->constructUserDn($username);
        }

        // Only log the DN in debug mode — it exposes OU structure.
        if ($isDebug) {
            Logger::info('User found', ['dn' => $entries[0]['dn']]);
        }
        return $entries[0]['dn'];
    }
    
    /**
     * Construct user DN directly (fallback method)
     */
    private function constructUserDn(string $username): string
    {
        // Extract domain from base DN and create UPN format
        // Example: OU=Active,OU=Users,OU=Corp,DC=FCCL,DC=local -> sbastola@fccl.local
        if (preg_match_all('/DC=([^,]+)/', $this->config['base_dn'], $matches)) {
            $domain = implode('.', $matches[1]);
            return $username . '@' . $domain;
        }
        
        // Fallback to CN format
        return "CN={$username},CN=Users,{$this->config['base_dn']}";
    }
    
    /**
     * Get user details
     */
    private function getUserDetails(string $userDn, string $username): array
    {
        // If userDn is in UPN format (contains @), search for the user
        if (strpos($userDn, '@') !== false) {
            $allowedSearchAttrs = ['sAMAccountName', 'userPrincipalName', 'uid', 'mail'];
            $searchAttr = in_array($this->config['user_search_attribute'], $allowedSearchAttrs, true)
                ? $this->config['user_search_attribute']
                : 'sAMAccountName';
            $safeUsername = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
            $filter = "(&{$this->config['user_filter']}({$searchAttr}={$safeUsername}))";
            
            $search = @ldap_search(
                $this->connection,
                $this->config['base_dn'],
                $filter,
                $this->config['user_attributes']
            );
            
            if ($search) {
                $entries = ldap_get_entries($this->connection, $search);
                if ($entries['count'] > 0) {
                    return $entries[0];
                }
            }
            
            // If search fails, return minimal info
            return [
                'samaccountname' => [$username],
                'displayname' => [$username],
                'mail' => [''],
            ];
        }
        
        // If userDn is a full DN, read directly
        $search = @ldap_read(
            $this->connection,
            $userDn,
            '(objectClass=*)',
            $this->config['user_attributes']
        );
        
        if (!$search) {
            return [];
        }
        
        $entries = ldap_get_entries($this->connection, $search);
        
        return $entries[0] ?? [];
    }
    
    /**
     * Check if user is in required group
     */
    private function isUserInGroup(array $userDetails, string $requiredGroup): bool
    {
        $groupAttr = strtolower($this->config['group_attribute']);
        
        if (!isset($userDetails[$groupAttr])) {
            return false;
        }
        
        $groups = $userDetails[$groupAttr];
        $count = $groups['count'] ?? 0;
        
        for ($i = 0; $i < $count; $i++) {
            if (stripos($groups[$i], $requiredGroup) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user is in any of the allowed groups
     */
    private function isUserInAnyGroup(array $userDetails, array $allowedGroups): bool
    {
        foreach ($allowedGroups as $group) {
            $group = trim((string)$group);
            if ($group !== '' && $this->isUserInGroup($userDetails, $group)) {
                return true;
            }
        }

        return false;
    }

    private function determineRole(array $userDetails): string
    {
        $adminGroup = $this->config['admin_group'] ?? '';
        if (!empty($adminGroup) && $this->isUserInGroup($userDetails, $adminGroup)) {
            return 'admin';
        }

        $userGroup = $this->config['user_group'] ?? '';
        if (!empty($userGroup) && $this->isUserInGroup($userDetails, $userGroup)) {
            return 'user';
        }

        return 'user';
    }
    
    /**
     * Extract group names from user details
     */
    private function extractGroups(array $userDetails): array
    {
        $groupAttr = strtolower($this->config['group_attribute']);
        
        if (!isset($userDetails[$groupAttr])) {
            return [];
        }
        
        $groups = [];
        $memberOf = $userDetails[$groupAttr];
        $count = $memberOf['count'] ?? 0;
        
        for ($i = 0; $i < $count; $i++) {
            // Extract CN from DN (e.g., "CN=GroupName,OU=..." -> "GroupName")
            if (preg_match('/^CN=([^,]+)/', $memberOf[$i], $matches)) {
                $groups[] = $matches[1];
            }
        }
        
        return $groups;
    }

    private function extractFirstName(string $displayName, string $fallback): string
    {
        $displayName = trim($displayName);
        if ($displayName === '') {
            return $fallback;
        }

        $parts = preg_split('/\s+/', $displayName);
        return $parts[0] ?? $fallback;
    }
    
    /**
     * Disconnect from LDAP server
     */
    private function disconnect(): void
    {
        if ($this->connection) {
            @ldap_unbind($this->connection);
            $this->connection = null;
        }
    }
    
    /**
     * Verify LDAP extension is loaded
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('ldap');
    }
}
