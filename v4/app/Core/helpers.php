<?php
/**
 * Global Helper Functions
 */

if (!function_exists('url')) {
    /**
     * Generate a URL with the correct base path
     */
    function url(string $path = ''): string
    {
        $basePath = '';

        if (!empty($_ENV['APP_URL'])) {
            $basePath = rtrim(parse_url($_ENV['APP_URL'], PHP_URL_PATH) ?: '', '/');
        }

        if ($basePath === '') {
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
            $basePath = preg_replace('#/public$#', '', $scriptDir);
            if ($basePath === '/') {
                $basePath = '';
            }
        }

        $path = '/' . ltrim($path, '/');
        return $basePath . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL
     */
    function asset(string $path): string
    {
        return url($path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a URL
     */
    function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     */
    function old(string $key, $default = '')
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}
