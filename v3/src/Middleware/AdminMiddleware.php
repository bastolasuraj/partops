<?php
declare(strict_types=1);

namespace PartOps\Middleware;

use PartOps\Services\Auth;

class AdminMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: /login');
            return false;
        }

        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo 'Access denied. Admin privileges required.';
            return false;
        }

        return true;
    }
}
