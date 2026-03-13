<?php

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\ErrorLog;

class AuditLogController extends BaseController
{
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

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            Response::error('Forbidden', 403);
        }
    }

    private function requireLogsAccess(): void
    {
        if (!AuditLogger::hasRecentLogsAccessVerification()) {
            Response::error('Admin password confirmation required', 423);
        }
    }

    public function accessStatus(): void
    {
        $this->requireAdmin();

        AuditLog::ensureTableExists();

        Response::success([
            'verified' => AuditLogger::hasRecentLogsAccessVerification(),
            'verified_until' => AuditLogger::getLogsAccessVerifiedUntil(),
        ]);
    }

    public function confirmAccess(): void
    {
        $this->requireAdmin();

        $password = trim((string)$this->request->get('password', ''));
        if ($password === '') {
            Response::error('Password is required', 422);
        }

        if (!AuditLogger::verifyCurrentAdminPassword($password)) {
            AuditLogger::recordUserEvent(
                'logs_access_denied',
                'Failed admin password confirmation for logs access',
                'failure'
            );

            Response::error('Incorrect password', 403);
        }

        $verifiedUntil = AuditLogger::markLogsAccessVerified();

        AuditLogger::recordUserEvent(
            'logs_access_granted',
            'Confirmed admin password for logs access',
            'success',
            [
                'metadata' => ['verified_until' => $verifiedUntil],
            ]
        );

        Response::success([
            'verified' => true,
            'verified_until' => $verifiedUntil,
        ], 'Password confirmed');
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->requireLogsAccess();

        $allowedPerPage = AuditLog::getPageSizeOptions();
        $defaultPerPage = AuditLog::getDefaultPageSize();
        $requestedPerPage = (int)$this->request->query('per_page', $this->request->query('limit', $defaultPerPage));
        $perPage = in_array($requestedPerPage, $allowedPerPage, true) ? $requestedPerPage : $defaultPerPage;
        $sharedPage = max(1, (int)$this->request->query('page', 1));
        $userPage = max(1, (int)$this->request->query('user_page', $sharedPage));
        $actionPage = max(1, (int)$this->request->query('action_page', $sharedPage));

        $userLogs = AuditLog::paginateByCategory('user', $userPage, $perPage);
        $actionLogs = AuditLog::paginateByCategory('action', $actionPage, $perPage);
        $errorLogs = ErrorLog::getRecent();

        Response::success([
            'verified_until' => AuditLogger::getLogsAccessVerifiedUntil(),
            'user_logs' => $userLogs['data'],
            'action_logs' => $actionLogs['data'],
            'error_logs' => $errorLogs,
            'user_pagination' => $userLogs['pagination'],
            'action_pagination' => $actionLogs['pagination'],
            'error_count' => ErrorLog::countCurrent(),
            'error_limit' => ErrorLog::getRecentLimit(),
            'per_page' => $perPage,
            'default_per_page' => $defaultPerPage,
            'page_size_options' => $allowedPerPage,
            'retention_limit' => AuditLog::getActiveLogLimit(),
            'archive_file' => AuditLog::getArchiveFileName(),
        ]);
    }

    public function reportError(): void
    {
        $this->requireAuth();

        $payload = $this->request->all();
        $message = trim((string)($payload['message'] ?? ''));

        // Limit payload sizes to prevent log flooding / DB bloat.
        $message    = mb_substr($message, 0, 2000);
        $stackTrace = mb_substr((string)($payload['stack_trace'] ?? ''), 0, 5000);

        if ($message === '') {
            Response::error('Error message is required', 422);
        }

        AuditLogger::recordClientError([
            'source' => $payload['source'] ?? 'frontend',
            'error_kind' => $payload['error_kind'] ?? 'client_error',
            'message' => $message,
            'stack_trace' => $stackTrace !== '' ? $stackTrace : null,
            'status_code' => $payload['status_code'] ?? null,
            'http_method' => $payload['http_method'] ?? 'CLIENT',
            'route_path' => $payload['route_path'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => $payload['user_agent'] ?? null,
            'file_name' => $payload['file_name'] ?? null,
            'line_number' => $payload['line_number'] ?? null,
            'column_number' => $payload['column_number'] ?? null,
            'status_text' => $payload['status_text'] ?? null,
            'current_url' => $payload['current_url'] ?? null,
            'request_url' => $payload['request_url'] ?? null,
            'api_message' => $payload['api_message'] ?? null,
            'endpoint' => $payload['endpoint'] ?? null,
            'reason_type' => $payload['reason_type'] ?? null,
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        ]);

        Response::created([
            'recorded' => true,
        ], 'Error recorded');
    }
}
