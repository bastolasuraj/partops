<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\Setting;

class SettingsController extends BaseController
{
    private Setting $settings;
    private array $operationalTables = [
        'vendor_returns',
        'inventory_transactions',
        'inventory_location_levels',
        'inventory_levels',
        'fowler_supplier_mapping',
        'work_orders',
        'parts',
        'technicians',
        'units',
        'suppliers',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->settings = new Setting();
    }

    private function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            Response::error('Not authenticated', 401);
        }
    }

    private function requireAdmin(): void
    {
        $this->requireAuth();

        $role = $_SESSION['user']['role'] ?? '';
        if ($role !== 'admin') {
            Response::error('Forbidden', 403);
        }
    }

    public function show(): void
    {
        $this->requireAuth();

        $data = $this->settings->getMany([
            'allow_untracked_returns',
            'audit_logs_page_size',
            'audit_logs_retention_limit',
        ]);
        $allow = isset($data['allow_untracked_returns']) ? (bool)$data['allow_untracked_returns'] : false;
        $pageSize = $this->normalizeLogPageSize($data['audit_logs_page_size'] ?? AuditLog::getDefaultPageSize());
        $retentionLimit = $this->normalizeRetentionLimit($data['audit_logs_retention_limit'] ?? AuditLog::getActiveLogLimit());

        Response::success([
            'allow_untracked_returns' => $allow,
            'audit_logs_page_size' => $pageSize,
            'audit_logs_retention_limit' => $retentionLimit,
            'audit_logs_page_size_options' => AuditLog::getPageSizeOptions(),
            'audit_logs_archive_file' => AuditLog::getArchiveFileName(),
        ]);
    }

    public function update(): void
    {
        $this->requireAdmin();

        $data = $this->request->all();
        $knownKeys = [
            'allow_untracked_returns',
            'audit_logs_page_size',
            'audit_logs_retention_limit',
        ];
        $providedKeys = array_values(array_intersect($knownKeys, array_keys($data)));

        if (empty($providedKeys)) {
            Response::error('No settings provided', 422);
        }

        $response = [];

        if (array_key_exists('allow_untracked_returns', $data)) {
            $allow = $this->normalizeBoolean($data['allow_untracked_returns']);
            $this->settings->set('allow_untracked_returns', $allow);
            $response['allow_untracked_returns'] = $allow;
        }

        if (array_key_exists('audit_logs_page_size', $data)) {
            $pageSize = $this->normalizeLogPageSize($data['audit_logs_page_size']);
            $this->settings->set('audit_logs_page_size', $pageSize);
            $response['audit_logs_page_size'] = $pageSize;
        }

        if (array_key_exists('audit_logs_retention_limit', $data)) {
            $retentionLimit = $this->normalizeRetentionLimit($data['audit_logs_retention_limit']);
            $this->settings->set('audit_logs_retention_limit', $retentionLimit);
            AuditLog::enforceRetention();
            $response['audit_logs_retention_limit'] = $retentionLimit;
        }

        $response['audit_logs_page_size_options'] = AuditLog::getPageSizeOptions();
        $response['audit_logs_archive_file'] = AuditLog::getArchiveFileName();

        Response::success($response, 'Settings updated');
    }

    public function truncateData(): void
    {
        $this->requireAdmin();

        $mode = strtolower(trim((string)$this->request->get('mode', 'operational')));
        if (!in_array($mode, ['operational', 'selective', 'wipe_all'], true)) {
            Response::error('Invalid mode. Use: operational, selective, or wipe_all', 422);
        }

        $expectedConfirmation = $mode === 'wipe_all'
            ? 'WIPE ALL DATA'
            : ($mode === 'selective' ? 'DELETE SELECTED DATA' : 'DELETE ALL DATA');
        $confirmText = trim((string)$this->request->get('confirm_text', ''));
        if ($confirmText !== $expectedConfirmation) {
            Response::error("Confirmation text must be exactly: {$expectedConfirmation}", 422);
        }

        $allTables = $this->getDatabaseTables();
        $allTableSet = array_flip($allTables);
        $tablesToTruncate = [];

        if ($mode === 'wipe_all') {
            $tablesToTruncate = $allTables;
        } elseif ($mode === 'selective') {
            $requestedTables = $this->request->get('tables', []);
            if (!is_array($requestedTables) || empty($requestedTables)) {
                Response::error('Select at least one table for selective truncation', 422);
            }

            $invalidTables = [];
            foreach ($requestedTables as $table) {
                $name = trim((string)$table);
                if ($name === '' || !$this->isSafeTableName($name) || !isset($allTableSet[$name])) {
                    $invalidTables[] = $name;
                    continue;
                }
                $tablesToTruncate[] = $name;
            }

            $tablesToTruncate = array_values(array_unique($tablesToTruncate));
            if (!empty($invalidTables)) {
                Response::error('Invalid table names provided', 422, $invalidTables);
            }
            if (empty($tablesToTruncate)) {
                Response::error('No valid tables selected for truncation', 422);
            }
        } else {
            foreach ($this->operationalTables as $table) {
                if (isset($allTableSet[$table])) {
                    $tablesToTruncate[] = $table;
                }
            }
        }

        $truncatedTables = [];
        try {
            Database::query('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tablesToTruncate as $table) {
                Database::query("TRUNCATE TABLE `{$table}`");
                $truncatedTables[] = $table;
            }
        } catch (\Throwable $e) {
            try {
                Database::query('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Throwable $ignored) {
            }
            Response::error('Failed to truncate data', 500, $e->getMessage());
        }

        Database::query('SET FOREIGN_KEY_CHECKS = 1');

        if (in_array('users', $truncatedTables, true)) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'] ?? '/',
                    $params['domain'] ?? '',
                    (bool)($params['secure'] ?? false),
                    (bool)($params['httponly'] ?? true)
                );
            }
            session_destroy();
        }

        Response::success([
            'mode' => $mode,
            'truncated_tables' => $truncatedTables,
            'count' => count($truncatedTables),
        ], 'Data truncation completed');
    }

    public function databaseTables(): void
    {
        $this->requireAuth();

        $allTables = $this->getDatabaseTables();
        $allTableSet = array_flip($allTables);
        $defaultOperational = [];
        foreach ($this->operationalTables as $table) {
            if (isset($allTableSet[$table])) {
                $defaultOperational[] = $table;
            }
        }

        Response::success([
            'tables' => $allTables,
            'default_operational_tables' => $defaultOperational,
            'confirmations' => [
                'selective' => 'DELETE SELECTED DATA',
                'wipe_all' => 'WIPE ALL DATA',
                'operational' => 'DELETE ALL DATA',
            ],
        ]);
    }

    private function getDatabaseTables(): array
    {
        $tables = [];
        try {
            // Prefer SHOW TABLES for compatibility with restricted DB permissions.
            $rows = Database::query('SHOW TABLES')->fetchAll();
            foreach ($rows as $row) {
                if (!is_array($row) || empty($row)) {
                    continue;
                }

                $name = '';
                if (isset($row['table_name'])) {
                    $name = trim((string)$row['table_name']);
                } else {
                    $first = reset($row);
                    $name = trim((string)$first);
                }

                if ($name !== '' && $this->isSafeTableName($name)) {
                    $tables[] = $name;
                }
            }
        } catch (\Throwable $ignored) {
            // Fallback to information_schema when SHOW TABLES is unavailable.
        }

        if (empty($tables)) {
            $rows = Database::query(
                "SELECT table_name
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_type = 'BASE TABLE'
                 ORDER BY table_name ASC"
            )->fetchAll();

            foreach ($rows as $row) {
                $name = trim((string)($row['table_name'] ?? ''));
                if ($name !== '' && $this->isSafeTableName($name)) {
                    $tables[] = $name;
                }
            }
        }

        $tables = array_values(array_unique($tables));
        sort($tables);
        return $tables;
    }

    private function isSafeTableName(string $table): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_]+$/', $table);
    }

    private function normalizeBoolean($rawValue): bool
    {
        if (is_bool($rawValue)) {
            return $rawValue;
        }

        if (is_numeric($rawValue)) {
            return ((int)$rawValue) === 1;
        }

        if (is_string($rawValue)) {
            return in_array(strtolower($rawValue), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private function normalizeLogPageSize($rawValue): int
    {
        $pageSize = is_numeric($rawValue) ? (int)$rawValue : AuditLog::getDefaultPageSize();
        if (!in_array($pageSize, AuditLog::getPageSizeOptions(), true)) {
            Response::error('Audit log page size must be one of: 10, 25, 50, 100', 422);
        }

        return $pageSize;
    }

    private function normalizeRetentionLimit($rawValue): int
    {
        if (!is_numeric($rawValue)) {
            Response::error('Audit log retention limit must be a whole number', 422);
        }

        $limit = (int)$rawValue;
        if ($limit < 1 || $limit > 100000) {
            Response::error('Audit log retention limit must be between 1 and 100000', 422);
        }

        return $limit;
    }
}
