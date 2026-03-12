<?php

namespace App\Core;

use App\Models\AuditLog;
use App\Models\ErrorLog;
use App\Models\User;

class AuditLogger
{
    private const LOGS_ACCESS_SESSION_KEY = 'audit_logs_verified_at';
    private const LOGS_ACCESS_WINDOW_SECONDS = 1800;

    private static bool $writing = false;

    public static function captureResponse(int $statusCode, $response): void
    {
        if (PHP_SAPI === 'cli' || self::$writing) {
            return;
        }

        $path = self::normalizePath($_SERVER['REQUEST_URI'] ?? '/');
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!self::shouldAutoLog($method, $path)) {
            return;
        }

        [$action, $description] = self::describeRoute($method, $path);
        [$resourceType, $resourceId] = self::extractResource($path);
        $requestPayload = self::sanitize(self::readRequestBody());
        $responseMessage = is_array($response) ? ($response['message'] ?? null) : null;

        self::record([
            'category' => 'action',
            'action' => $action,
            'description' => $description,
            'outcome' => $statusCode >= 400 ? 'failure' : 'success',
            'http_method' => $method,
            'route_path' => $path,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'request_payload' => $requestPayload,
            'metadata' => array_filter([
                'query' => self::sanitize($_GET),
                'status_code' => $statusCode,
                'response_message' => $responseMessage,
                'success' => is_array($response) ? ($response['success'] ?? ($statusCode < 400)) : ($statusCode < 400),
            ], static fn ($value) => $value !== null && $value !== []),
        ]);
    }

    public static function recordUserEvent(string $action, string $description, string $outcome = 'success', array $context = []): void
    {
        self::record(array_merge($context, [
            'category' => 'user',
            'action' => $action,
            'description' => $description,
            'outcome' => $outcome,
            'http_method' => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'POST'),
            'route_path' => self::normalizePath($_SERVER['REQUEST_URI'] ?? '/'),
        ]));
    }

    public static function recordActionEvent(string $action, string $description, array $context = [], string $outcome = 'success'): void
    {
        self::record(array_merge($context, [
            'category' => 'action',
            'action' => $action,
            'description' => $description,
            'outcome' => $outcome,
            'http_method' => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            'route_path' => self::normalizePath($_SERVER['REQUEST_URI'] ?? '/'),
        ]));
    }

    public static function captureErrorResponse(string $message, int $statusCode, $errors = null, array $context = []): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $path = self::normalizePath((string)($context['route_path'] ?? ($_SERVER['REQUEST_URI'] ?? '/')));
        if (self::shouldSkipErrorLogging($path)) {
            return;
        }

        $metadata = $context['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = ['details' => self::sanitize($metadata)];
        }

        if ($errors !== null) {
            $metadata['errors'] = self::sanitize($errors);
        }

        $query = self::sanitize($_GET);
        if ($query !== []) {
            $metadata['query'] = $query;
        }

        $origin = $context['origin'] ?? ($_SERVER['HTTP_ORIGIN'] ?? null);
        if ($origin !== null && $origin !== '') {
            $metadata['origin'] = $origin;
        }

        self::recordError([
            'source' => $context['source'] ?? 'backend',
            'error_kind' => $context['error_kind'] ?? ($statusCode >= 500 ? 'server_error' : 'request_error'),
            'message' => $message,
            'stack_trace' => $context['stack_trace'] ?? null,
            'status_code' => $statusCode,
            'http_method' => $context['http_method'] ?? strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            'route_path' => $path,
            'ip_address' => $context['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            'user_agent' => $context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            'metadata' => $metadata,
        ]);
    }

    public static function recordClientError(array $payload): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $path = self::normalizePath((string)($payload['route_path'] ?? ($_SERVER['HTTP_REFERER'] ?? ($_SERVER['REQUEST_URI'] ?? '/'))));
        if (self::shouldSkipErrorLogging($path)) {
            return;
        }

        $metadata = $payload['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = ['details' => self::sanitize($metadata)];
        }

        foreach ([
            'file_name' => 'file_name',
            'line_number' => 'line_number',
            'column_number' => 'column_number',
            'status_text' => 'status_text',
            'current_url' => 'current_url',
            'request_url' => 'request_url',
            'api_message' => 'api_message',
            'endpoint' => 'endpoint',
            'reason_type' => 'reason_type',
        ] as $inputKey => $metadataKey) {
            if (!array_key_exists($inputKey, $payload)) {
                continue;
            }

            $value = self::sanitize($payload[$inputKey]);
            if ($value !== null && $value !== '') {
                $metadata[$metadataKey] = $value;
            }
        }

        self::recordError([
            'source' => $payload['source'] ?? 'frontend',
            'error_kind' => $payload['error_kind'] ?? 'client_error',
            'message' => $payload['message'] ?? 'Client error recorded',
            'stack_trace' => $payload['stack_trace'] ?? null,
            'status_code' => self::normalizeStatusCode($payload['status_code'] ?? null),
            'http_method' => $payload['http_method'] ?? 'CLIENT',
            'route_path' => $path,
            'ip_address' => $payload['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            'user_agent' => $payload['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            'metadata' => $metadata,
        ]);
    }

    public static function recordPhpShutdownError(array $error): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        $type = (int)($error['type'] ?? 0);
        if (!in_array($type, $fatalTypes, true)) {
            return;
        }

        self::recordError([
            'source' => 'backend',
            'error_kind' => 'fatal_shutdown_error',
            'message' => (string)($error['message'] ?? 'Fatal PHP shutdown error'),
            'http_method' => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            'route_path' => self::normalizePath($_SERVER['REQUEST_URI'] ?? '/'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'metadata' => array_filter([
                'error_type' => $type,
                'file_name' => $error['file'] ?? null,
                'line_number' => $error['line'] ?? null,
            ], static fn ($value) => $value !== null && $value !== ''),
        ]);
    }

    public static function hasRecentLogsAccessVerification(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            return false;
        }

        $verifiedAt = (int)($_SESSION[self::LOGS_ACCESS_SESSION_KEY] ?? 0);
        return $verifiedAt > 0 && (time() - $verifiedAt) <= self::LOGS_ACCESS_WINDOW_SECONDS;
    }

    public static function markLogsAccessVerified(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[self::LOGS_ACCESS_SESSION_KEY] = time();
        return date('c', $_SESSION[self::LOGS_ACCESS_SESSION_KEY] + self::LOGS_ACCESS_WINDOW_SECONDS);
    }

    public static function clearLogsAccessVerification(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::LOGS_ACCESS_SESSION_KEY]);
    }

    public static function getLogsAccessVerifiedUntil(): ?string
    {
        if (!self::hasRecentLogsAccessVerification()) {
            return null;
        }

        $verifiedAt = (int)($_SESSION[self::LOGS_ACCESS_SESSION_KEY] ?? 0);
        return date('c', $verifiedAt + self::LOGS_ACCESS_WINDOW_SECONDS);
    }

    public static function verifyCurrentAdminPassword(string $password): bool
    {
        if ($password === '') {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionUser = $_SESSION['user'] ?? [];
        if (($sessionUser['role'] ?? '') !== 'admin') {
            return false;
        }

        $username = trim((string)($sessionUser['username'] ?? ''));
        if ($username === '') {
            return false;
        }

        $authType = $sessionUser['auth_type'] ?? 'local';

        if ($authType === 'local') {
            $user = User::findByUsername($username);
            return $user !== null && $user->isAdmin() && $user->verifyPassword($password);
        }

        if (!LdapAuth::isAvailable()) {
            return false;
        }

        try {
            $ldap = new LdapAuth();
            return (bool)$ldap->authenticate($username, $password);
        } catch (\Throwable $e) {
            Logger::warning('Audit logs password verification failed', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private static function record(array $entry): void
    {
        if (self::$writing) {
            return;
        }

        self::$writing = true;

        try {
            $sessionUser = $_SESSION['user'] ?? [];

            AuditLog::create([
                'category' => $entry['category'] ?? 'action',
                'action' => $entry['action'] ?? 'unknown',
                'description' => $entry['description'] ?? 'Activity recorded',
                'outcome' => $entry['outcome'] ?? 'success',
                'user_id' => $entry['user_id'] ?? ($sessionUser['id'] ?? null),
                'username' => $entry['username'] ?? ($sessionUser['username'] ?? null),
                'display_name' => $entry['display_name'] ?? ($sessionUser['display_name'] ?? null),
                'user_role' => $entry['user_role'] ?? ($sessionUser['role'] ?? null),
                'auth_type' => $entry['auth_type'] ?? ($sessionUser['auth_type'] ?? null),
                'http_method' => $entry['http_method'] ?? strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
                'route_path' => $entry['route_path'] ?? self::normalizePath($_SERVER['REQUEST_URI'] ?? '/'),
                'ip_address' => $entry['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                'user_agent' => $entry['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                'origin' => $entry['origin'] ?? ($_SERVER['HTTP_ORIGIN'] ?? null),
                'resource_type' => $entry['resource_type'] ?? null,
                'resource_id' => $entry['resource_id'] ?? null,
                'request_payload' => $entry['request_payload'] ?? null,
                'metadata' => $entry['metadata'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Failed to write audit log', ['error' => $e->getMessage()]);
        } finally {
            self::$writing = false;
        }
    }

    private static function recordError(array $entry): void
    {
        if (self::$writing) {
            return;
        }

        self::$writing = true;

        try {
            $sessionUser = $_SESSION['user'] ?? [];

            ErrorLog::create([
                'source' => $entry['source'] ?? 'backend',
                'error_kind' => $entry['error_kind'] ?? 'application_error',
                'message' => self::trimText($entry['message'] ?? 'Application error recorded', 65535),
                'stack_trace' => self::trimText($entry['stack_trace'] ?? null, 65535),
                'status_code' => self::normalizeStatusCode($entry['status_code'] ?? null),
                'user_id' => $entry['user_id'] ?? ($sessionUser['id'] ?? null),
                'username' => $entry['username'] ?? ($sessionUser['username'] ?? null),
                'display_name' => $entry['display_name'] ?? ($sessionUser['display_name'] ?? null),
                'user_role' => $entry['user_role'] ?? ($sessionUser['role'] ?? null),
                'http_method' => strtoupper((string)($entry['http_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'))),
                'route_path' => $entry['route_path'] ?? self::normalizePath($_SERVER['REQUEST_URI'] ?? '/'),
                'ip_address' => $entry['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
                'user_agent' => $entry['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                'metadata' => self::sanitize($entry['metadata'] ?? null),
            ]);
        } catch (\Throwable $e) {
            Logger::warning('Failed to write error log', ['error' => $e->getMessage()]);
        } finally {
            self::$writing = false;
        }
    }

    private static function shouldAutoLog(string $method, string $path): bool
    {
        if ($path === '/' || $path === '/readme.html') {
            return false;
        }

        if (strpos($path, '/auth/') === 0) {
            return false;
        }

        if (strpos($path, '/audit-logs') === 0) {
            return false;
        }

        if ($method === 'GET') {
            return in_array($path, [
                '/reports/archive/download',
            ], true);
        }

        return true;
    }

    private static function shouldSkipErrorLogging(string $path): bool
    {
        return strpos($path, '/audit-logs/errors') === 0;
    }

    private static function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = preg_replace('#^/api#', '', $path);
        return $path === '' ? '/' : $path;
    }

    private static function describeRoute(string $method, string $path): array
    {
        $definitions = [
            ['GET', '#^/suppliers$#', 'suppliers.index', 'Viewed suppliers'],
            ['GET', '#^/suppliers/[^/]+$#', 'suppliers.show', 'Viewed supplier details'],
            ['POST', '#^/suppliers$#', 'suppliers.store', 'Created supplier'],
            ['PUT', '#^/suppliers/[^/]+$#', 'suppliers.update', 'Updated supplier'],
            ['DELETE', '#^/suppliers/[^/]+$#', 'suppliers.destroy', 'Deleted supplier'],
            ['GET', '#^/technicians$#', 'technicians.index', 'Viewed technicians'],
            ['GET', '#^/technicians/[^/]+$#', 'technicians.show', 'Viewed technician details'],
            ['POST', '#^/technicians$#', 'technicians.store', 'Created technician'],
            ['PUT', '#^/technicians/[^/]+$#', 'technicians.update', 'Updated technician'],
            ['DELETE', '#^/technicians/[^/]+$#', 'technicians.destroy', 'Deleted technician'],
            ['GET', '#^/units$#', 'units.index', 'Viewed units'],
            ['GET', '#^/units/[^/]+$#', 'units.show', 'Viewed unit details'],
            ['POST', '#^/units$#', 'units.store', 'Created unit'],
            ['PUT', '#^/units/[^/]+$#', 'units.update', 'Updated unit'],
            ['DELETE', '#^/units/[^/]+$#', 'units.destroy', 'Deleted unit'],
            ['GET', '#^/parts$#', 'parts.index', 'Viewed parts list'],
            ['GET', '#^/parts/low-stock$#', 'parts.low_stock', 'Viewed low stock parts'],
            ['GET', '#^/parts/check-duplicate$#', 'parts.check_duplicate', 'Checked duplicate supplier part number'],
            ['GET', '#^/parts/check-fowler$#', 'parts.check_fowler', 'Checked Fowler part number'],
            ['GET', '#^/parts/next-fowler$#', 'parts.next_fowler', 'Requested next Fowler part number'],
            ['GET', '#^/parts/fowler-mapping$#', 'parts.fowler_mapping', 'Viewed Fowler mapping'],
            ['POST', '#^/parts/bulk-import/analyze$#', 'parts.bulk_import_analyze', 'Analyzed bulk import'],
            ['POST', '#^/parts/bulk-import$#', 'parts.bulk_import', 'Processed bulk import'],
            ['GET', '#^/parts/[^/]+$#', 'parts.show', 'Viewed part details'],
            ['GET', '#^/parts/[^/]+/stock$#', 'parts.stock', 'Viewed part stock'],
            ['GET', '#^/parts/[^/]+/locations$#', 'parts.locations', 'Viewed part locations'],
            ['POST', '#^/parts$#', 'parts.store', 'Created part'],
            ['PUT', '#^/parts/[^/]+$#', 'parts.update', 'Updated part'],
            ['DELETE', '#^/parts/[^/]+$#', 'parts.destroy', 'Archived part'],
            ['GET', '#^/work-orders$#', 'work_orders.index', 'Viewed work orders'],
            ['GET', '#^/work-orders/open$#', 'work_orders.open', 'Viewed open work orders'],
            ['GET', '#^/work-orders/[^/]+$#', 'work_orders.show', 'Viewed work order details'],
            ['POST', '#^/work-orders$#', 'work_orders.store', 'Created work order'],
            ['PUT', '#^/work-orders/[^/]+$#', 'work_orders.update', 'Updated work order'],
            ['DELETE', '#^/work-orders/[^/]+$#', 'work_orders.destroy', 'Deleted work order'],
            ['GET', '#^/inventory/stock-levels$#', 'inventory.stock_levels', 'Viewed stock levels'],
            ['GET', '#^/inventory/transactions$#', 'inventory.transactions', 'Viewed transactions'],
            ['GET', '#^/inventory/returnable-items$#', 'inventory.returnable_items', 'Viewed returnable items'],
            ['POST', '#^/inventory/incoming$#', 'inventory.incoming', 'Processed incoming stock'],
            ['POST', '#^/inventory/checkout$#', 'inventory.checkout', 'Processed outgoing checkout'],
            ['POST', '#^/inventory/return$#', 'inventory.return', 'Processed return to stock'],
            ['GET', '#^/vendor-returns$#', 'vendor_returns.index', 'Viewed vendor returns'],
            ['GET', '#^/vendor-returns/[^/]+$#', 'vendor_returns.show', 'Viewed vendor return details'],
            ['POST', '#^/vendor-returns$#', 'vendor_returns.store', 'Created vendor return'],
            ['PUT', '#^/vendor-returns/[^/]+$#', 'vendor_returns.update', 'Updated vendor return'],
            ['DELETE', '#^/vendor-returns/[^/]+$#', 'vendor_returns.destroy', 'Deleted vendor return'],
            ['GET', '#^/settings$#', 'settings.show', 'Viewed settings'],
            ['PUT', '#^/settings$#', 'settings.update', 'Updated settings'],
            ['GET', '#^/settings/database-tables$#', 'settings.database_tables', 'Viewed database table list'],
            ['POST', '#^/settings/truncate-data$#', 'settings.truncate_data', 'Ran data truncation'],
            ['GET', '#^/reports/summary$#', 'reports.summary', 'Viewed summary report'],
            ['GET', '#^/reports/archive$#', 'reports.archive_list', 'Viewed archived reports'],
            ['GET', '#^/reports/archive/download$#', 'reports.archive_download', 'Downloaded archived report'],
            ['POST', '#^/reports/archive$#', 'reports.archive_store', 'Archived downloaded report'],
        ];

        foreach ($definitions as [$matchMethod, $pattern, $action, $description]) {
            if ($method === $matchMethod && preg_match($pattern, $path)) {
                return [$action, $description];
            }
        }

        $fallback = trim($path, '/');
        $fallback = $fallback === '' ? 'root' : str_replace(['/', '-'], ['.', '_'], $fallback);

        return [
            strtolower($method) . '.' . $fallback,
            "{$method} {$path}",
        ];
    }

    private static function extractResource(string $path): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn ($value) => $value !== ''));
        $resourceType = $segments[0] ?? null;
        $resourceId = null;

        foreach (array_slice($segments, 1) as $segment) {
            if (preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                $resourceId = $segment;
                break;
            }
        }

        return [$resourceType, $resourceId];
    }

    private static function readRequestBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?? [];
        }

        return $_POST;
    }

    private static function sanitize($value, ?string $key = null)
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'password_hash', 'token'];

        if ($key !== null && in_array(strtolower($key), $sensitiveKeys, true)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $itemKey => $itemValue) {
                $clean[$itemKey] = self::sanitize($itemValue, is_string($itemKey) ? $itemKey : null);
            }
            return $clean;
        }

        if (is_string($value) && strlen($value) > 1000) {
            return substr($value, 0, 1000) . '...';
        }

        return $value;
    }

    private static function trimText($value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string)$value);
        if ($string === '') {
            return null;
        }

        if (strlen($string) <= $maxLength) {
            return $string;
        }

        if ($maxLength <= 3) {
            return substr($string, 0, $maxLength);
        }

        return substr($string, 0, $maxLength - 3) . '...';
    }

    private static function normalizeStatusCode($statusCode): ?int
    {
        if ($statusCode === null || $statusCode === '') {
            return null;
        }

        if (!is_numeric($statusCode)) {
            return null;
        }

        return (int)$statusCode;
    }
}
