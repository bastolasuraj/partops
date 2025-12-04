<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '3306';
                $dbname = $_ENV['DB_NAME'] ?? 'partops_v4';
                $user = $_ENV['DB_USER'] ?? 'root';
                $pass = $_ENV['DB_PASS'] ?? '';

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                Logger::info('Database connection established', [
                    'host' => $host,
                    'database' => $dbname
                ]);
            } catch (PDOException $e) {
                Logger::logDatabase('Database connection failed: ' . $e->getMessage(), [
                    'host' => $host,
                    'database' => $dbname,
                    'code' => $e->getCode()
                ]);
                die("Database connection failed. Please check the error log.");
            }
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = self::getInstance()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::logDatabase('Query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params,
                'code' => $e->getCode()
            ]);
            throw $e;
        }
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }
}
