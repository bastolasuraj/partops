<?php
declare(strict_types=1);

namespace PartOps\Services;

class View
{
    private static string $layoutsPath = '';
    private static string $viewsPath = '';

    public static function init(): void
    {
        self::$layoutsPath = BASE_PATH . '/src/Views/layouts';
        self::$viewsPath = BASE_PATH . '/src/Views';
    }

    public static function render(string $view, array $data = [], ?string $layout = 'main'): void
    {
        self::init();
        
        extract($data);
        
        $viewPath = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: $view");
        }

        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        if ($layout) {
            $layoutPath = self::$layoutsPath . '/' . $layout . '.php';
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout not found: $layout");
            }
            include $layoutPath;
        } else {
            echo $content;
        }
    }

    public static function partial(string $partial, array $data = []): void
    {
        self::init();
        extract($data);
        $partialPath = self::$viewsPath . '/' . str_replace('.', '/', $partial) . '.php';
        if (file_exists($partialPath)) {
            include $partialPath;
        }
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
