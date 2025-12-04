<?php
declare(strict_types=1);

namespace PartOps\Services;

class RateLimiter
{
    private static function getKey(string $identifier): string
    {
        return "rate_limit_$identifier";
    }

    public static function check(string $identifier, int $maxRequests = null, int $windowSeconds = null): bool
    {
        $maxRequests = $maxRequests ?? (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 10);
        $windowSeconds = $windowSeconds ?? (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
        
        $key = self::getKey($identifier);
        $data = Session::get($key, ['count' => 0, 'reset_at' => time() + $windowSeconds]);

        if (time() > $data['reset_at']) {
            $data = ['count' => 0, 'reset_at' => time() + $windowSeconds];
        }

        if ($data['count'] >= $maxRequests) {
            return false;
        }

        $data['count']++;
        Session::set($key, $data);

        return true;
    }

    public static function remaining(string $identifier): int
    {
        $maxRequests = (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 10);
        $key = self::getKey($identifier);
        $data = Session::get($key, ['count' => 0]);
        return max(0, $maxRequests - $data['count']);
    }

    public static function reset(string $identifier): void
    {
        Session::remove(self::getKey($identifier));
    }
}
