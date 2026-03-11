<?php

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\BaseController;
use App\Core\Response;
use App\Models\AuditLog;

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

        Response::success([
            'verified_until' => AuditLogger::getLogsAccessVerifiedUntil(),
            'user_logs' => $userLogs['data'],
            'action_logs' => $actionLogs['data'],
            'user_pagination' => $userLogs['pagination'],
            'action_pagination' => $actionLogs['pagination'],
            'per_page' => $perPage,
            'default_per_page' => $defaultPerPage,
            'page_size_options' => $allowedPerPage,
            'retention_limit' => AuditLog::getActiveLogLimit(),
            'archive_file' => AuditLog::getArchiveFileName(),
        ]);
    }
}
