<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        return Database::fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        return Database::fetch($sql, [$id]);
    }

    public function where(string $column, mixed $value): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ?";
        return Database::fetchAll($sql, [$value]);
    }

    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        Database::query($sql, array_values($data));
        
        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = ?";
        }
        $setClause = implode(', ', $sets);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);
        
        Database::query($sql, $params);
        return true;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        Database::query($sql, [$id]);
        return true;
    }

    protected function query(string $sql, array $params = []): \PDOStatement
    {
        return Database::query($sql, $params);
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        return Database::fetch($sql, $params);
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return Database::fetchAll($sql, $params);
    }
}
