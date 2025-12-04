<?php
declare(strict_types=1);

namespace PartOps\Models;

use PartOps\Services\Database;

abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1";
        return Database::queryOne($sql, ['id' => $id]);
    }

    public static function all(array $conditions = [], string $orderBy = 'id DESC', int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM " . static::$table;
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :$key";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return Database::query($sql, $params);
    }

    public static function create(array $data): ?int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "INSERT INTO " . static::$table . " (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $rows = Database::execute($sql, $data);

        if ($rows > 0) {
            return (int)Database::lastInsertId();
        }

        return null;
    }

    public static function update(int $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "$key = :$key";
        }
        $data['id'] = $id;

        $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets) . " WHERE " . static::$primaryKey . " = :id";
        return Database::execute($sql, $data) > 0;
    }

    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id";
        return Database::execute($sql, ['id' => $id]) > 0;
    }

    public static function softDelete(int $id): bool
    {
        return self::update($id, ['is_active' => false, 'deleted_at' => date('Y-m-d H:i:s')]);
    }

    public static function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM " . static::$table;
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :$key";
                $params[$key] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = Database::queryOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }
}
