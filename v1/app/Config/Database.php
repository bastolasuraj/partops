<?php
/**
 * Database Connection Manager
 * 
 * Handle PDO database connections
 */

declare(strict_types=1);

namespace PartOps\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Create database connection
     */
    public static function connect(Config $config): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = $config->get('DB_HOST', 'localhost');
        $port = $config->get('DB_PORT', '3306');
        $dbname = $config->get('DB_NAME', 'partops');
        $user = $config->get('DB_USER', 'root');
        $pass = $config->get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);

            return self::$connection;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed");
        }
    }

    /**
     * Get existing connection
     */
    public static function getConnection(): ?PDO
    {
        return self::$connection;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        if (self::$connection === null) {
            throw new \RuntimeException("No database connection");
        }

        return self::$connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        if (self::$connection === null) {
            throw new \RuntimeException("No database connection");
        }

        return self::$connection->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        if (self::$connection === null) {
            throw new \RuntimeException("No database connection");
        }

        return self::$connection->rollBack();
    }
}
