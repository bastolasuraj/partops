<?php
declare(strict_types=1);

namespace PartOps\Middleware;

use PartOps\Services\Auth;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: /login');
            return false;
        }
        return true;
    }
}
