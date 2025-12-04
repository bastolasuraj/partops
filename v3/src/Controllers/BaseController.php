<?php
declare(strict_types=1);

namespace PartOps\Controllers;

use PartOps\Services\View;
use PartOps\Services\Session;
use PartOps\Services\Auth;
use PartOps\Services\CSRF;

abstract class BaseController
{
    protected function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        $data['user'] = Auth::user();
        $data['isAdmin'] = Auth::isAdmin();
        $data['csrf'] = CSRF::getField();
        $data['csrfToken'] = CSRF::getToken();
        $data['flash'] = [
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'info' => Session::getFlash('info')
        ];
        
        View::render($view, $data, $layout);
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function redirect(string $url, ?string $message = null, string $type = 'success'): void
    {
        if ($message) {
            Session::flash($type, $message);
        }
        header("Location: $url");
        exit;
    }

    protected function getInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            return array_merge($_GET, $input);
        }
        
        return array_merge($_GET, $_POST);
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function notFound(string $message = 'Not found'): void
    {
        http_response_code(404);
        if ($this->isAjax()) {
            $this->json(['error' => $message], 404);
        } else {
            echo $message;
        }
        exit;
    }

    protected function forbidden(string $message = 'Access denied'): void
    {
        http_response_code(403);
        if ($this->isAjax()) {
            $this->json(['error' => $message], 403);
        } else {
            echo $message;
        }
        exit;
    }
}
