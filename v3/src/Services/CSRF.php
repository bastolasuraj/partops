<?php
declare(strict_types=1);

namespace PartOps\Services;

class CSRF
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_TIME_KEY = '_csrf_token_time';

    public static function generate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::TOKEN_KEY, $token);
        Session::set(self::TOKEN_TIME_KEY, time());
        return $token;
    }

    public static function getToken(): string
    {
        $token = Session::get(self::TOKEN_KEY);
        if (!$token) {
            $token = self::generate();
        }
        return $token;
    }

    public static function validate(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $storedToken = Session::get(self::TOKEN_KEY);
        $tokenTime = Session::get(self::TOKEN_TIME_KEY, 0);
        $lifetime = (int)($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600);

        if (!$storedToken || !hash_equals($storedToken, $token)) {
            return false;
        }

        if (time() - $tokenTime > $lifetime) {
            self::generate();
            return false;
        }

        return true;
    }

    public static function getField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::getToken()) . '">';
    }
}
