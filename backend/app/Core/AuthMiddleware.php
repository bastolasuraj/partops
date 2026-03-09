<?php
namespace App\Core;

class AuthMiddleware
{
    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is in session
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        // Check if session has expired
        if (isset($_SESSION['expires_at']) && time() > $_SESSION['expires_at']) {
            $_SESSION = [];
            session_destroy();
            return false;
        }
        
        return true;
    }
    
    /**
     * Require authentication or return 401
     */
    public static function require(): void
    {
        if (!self::check()) {
            Response::error('Authentication required', 401);
            exit;
        }
    }
    
    /**
     * Get current authenticated user
     */
    public static function user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user'] ?? null;
    }
}
