<?php
declare(strict_types=1);

namespace PartOps\Middleware;

use PartOps\Services\CSRF;

class CSRFMiddleware
{
    public function handle(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!CSRF::validate($token)) {
                http_response_code(403);
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Invalid CSRF token']);
                } else {
                    echo 'Invalid security token. Please refresh and try again.';
                }
                return false;
            }
        }
        return true;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
