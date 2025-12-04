<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $layoutPath = __DIR__ . '/../Views/layout.php';
    private static array $data = [];

    public static function render(string $view, array $data = [], bool|string|null $layout = null): void
    {
        self::$data = $data;
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            die("View not found: {$view}");
        }

        if ($layout !== false) {
            $layoutPath = $layout ?? self::$layoutPath;
            if (file_exists($layoutPath)) {
                $content = self::getViewContent($viewPath, $data);
                include $layoutPath;
            } else {
                include $viewPath;
            }
        } else {
            include $viewPath;
        }
    }

    private static function getViewContent(string $viewPath, array $data): string
    {
        extract($data);
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }

    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $path): void
    {
        if (!preg_match('#^https?://#', $path)) {
            $path = function_exists('url') ? url($path) : $path;
        }
        header("Location: {$path}");
        exit;
    }

    public static function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }
}
