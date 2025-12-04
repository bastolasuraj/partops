<?php
declare(strict_types=1);

namespace PartOps\Middleware;

use PartOps\Services\RateLimiter;
use PartOps\Services\Auth;

class RateLimitMiddleware
{
    public function handle(): bool
    {
        $identifier = Auth::id() ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'stock_change_' . $identifier;

        if (!RateLimiter::check($key)) {
            http_response_code(429);
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Too many requests. Please wait before trying again.']);
            } else {
                echo 'Too many requests. Please wait a moment and try again.';
            }
            return false;
        }

        return true;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
