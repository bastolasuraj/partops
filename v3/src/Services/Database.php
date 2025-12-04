<?php
declare(strict_types=1);

namespace PartOps\Services;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = $_ENV['DATABASE_URL'] ?? '';
            
            $config = self::buildConfig($dsn);
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // MySQL needs emulate prepares for LIMIT/OFFSET placeholders
            if ($config['driver'] === 'mysql') {
                $options[PDO::ATTR_EMULATE_PREPARES] = true;
                $options[PDO::ATTR_STRINGIFY_FETCHES] = false;
            } else {
                $options[PDO::ATTR_EMULATE_PREPARES] = false;
            }

            try {
                self::$instance = new PDO($config['dsn'], $config['user'], $config['password'], $options);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw $e;
            }
        }

        return self::$instance;
    }

    private static function buildConfig(string $dsn): array
    {
        // Prefer DATABASE_URL if provided, support mysql:// and pgsql:// style URIs
        if (!empty($dsn)) {
            $parsed = parse_url($dsn);
            $scheme = $parsed['scheme'] ?? 'mysql';
            $driver = $scheme === 'pgsql' ? 'pgsql' : 'mysql';
            $host = $parsed['host'] ?? 'localhost';
            $port = $parsed['port'] ?? ($driver === 'pgsql' ? 5432 : 3306);
            $user = $parsed['user'] ?? ($driver === 'pgsql' ? 'postgres' : 'root');
            $password = $parsed['pass'] ?? '';
            $dbname = ltrim($parsed['path'] ?? '/partops', '/');

            $dsnTemplate = $driver === 'pgsql'
                ? '%s:host=%s;port=%d;dbname=%s'
                : '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4';
            $dsnString = sprintf($dsnTemplate, $driver, $host, $port, $dbname);

            return [
                'driver' => $driver,
                'dsn' => $dsnString,
                'user' => $user,
                'password' => $password,
            ];
        }

        // Fall back to discrete env vars (DB_* preferred, PG* for backwards compat)
        $host = $_ENV['DB_HOST'] ?? $_ENV['PGHOST'] ?? 'localhost';
        $port = (int)($_ENV['DB_PORT'] ?? $_ENV['PGPORT'] ?? 3306);
        $dbname = $_ENV['DB_NAME'] ?? $_ENV['PGDATABASE'] ?? 'partops';
        $user = $_ENV['DB_USER'] ?? $_ENV['PGUSER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? $_ENV['PGPASSWORD'] ?? '';
        $driver = 'mysql';

        $dsnTemplate = $driver === 'pgsql'
            ? '%s:host=%s;port=%d;dbname=%s'
            : '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4';
        $dsnString = sprintf($dsnTemplate, $driver, $host, $port, $dbname);

        return [
            'driver' => $driver,
            'dsn' => $dsnString,
            'user' => $user,
            'password' => $password,
        ];
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
